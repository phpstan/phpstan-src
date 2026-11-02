<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use PhpParser\Internal\TokenStream;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\ParserFactory;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileReader;
use PHPStan\Fixable\PhpPrinter;
use PHPStan\Fixable\PhpPrinterIndentationDetectorVisitor;
use PHPStan\ShouldNotHappenException;
use function array_is_list;
use function array_key_exists;
use function count;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function ltrim;
use function sprintf;
use function str_contains;
use function str_repeat;
use function strcasecmp;
use function strrpos;
use function strtolower;
use function substr;

/**
 * Adds attributes to classes and their constructor parameters, keeping the rest of the file as it was.
 */
#[AutowiredService]
final class PhpClassAttributesWriter
{

	private const DI_NAMESPACE = 'PHPStan\\DependencyInjection\\';

	/**
	 * @param list<ClassAttributes> $edits classes declared in the file
	 * @return string the new contents of the file
	 */
	public function write(string $file, array $edits): string
	{
		$code = FileReader::read($file);
		$parser = (new ParserFactory())->createForNewestSupportedVersion();
		$oldStmts = $parser->parse($code);
		if ($oldStmts === null) {
			throw new ShouldNotHappenException();
		}
		$tokens = $parser->getTokens();

		$indentDetector = new PhpPrinterIndentationDetectorVisitor(new TokenStream($tokens, PhpPrinter::TAB_WIDTH));
		/** @var list<Node\Stmt> $newStmts */
		$newStmts = (new NodeTraverser($indentDetector, new CloningVisitor()))->traverse($oldStmts);
		$indent = str_contains($indentDetector->indentCharacter, "\t")
			? "\t"
			: str_repeat($indentDetector->indentCharacter, $indentDetector->indentSize);

		foreach ($edits as $edit) {
			$this->addAttributes($file, $newStmts, $edit);
		}

		return (new AttributesPrinter(['indent' => $indent]))->printFormatPreserving($newStmts, $oldStmts, $tokens);
	}

	/**
	 * @param list<Node\Stmt> $stmts
	 */
	private function addAttributes(string $file, array &$stmts, ClassAttributes $edit): void
	{
		$className = $edit->reflection->getName();
		$namespace = $edit->reflection->getNamespaceName();
		$shortName = $edit->reflection->getShortName();

		if ($namespace === '') {
			$this->addAttributesInScope($stmts, '', $shortName, $edit);
			return;
		}

		foreach ($stmts as $stmt) {
			if (!$stmt instanceof Node\Stmt\Namespace_ || $stmt->name === null || strcasecmp($stmt->name->toString(), $namespace) !== 0) {
				continue;
			}
			if ($this->addAttributesInScope($stmt->stmts, $namespace, $shortName, $edit)) {
				return;
			}
		}

		throw new ShouldNotHappenException(sprintf('Class %s not found in %s.', $className, $file));
	}

	/**
	 * @param list<Node\Stmt> $stmts statements of a namespace, or of a file without one
	 */
	private function addAttributesInScope(array &$stmts, string $namespace, string $shortName, ClassAttributes $edit): bool
	{
		$class = null;
		foreach ($stmts as $stmt) {
			// an interface carries the attribute of a service created by a factory
			if (!($stmt instanceof Node\Stmt\Class_ || $stmt instanceof Node\Stmt\Interface_) || $stmt->name === null || strcasecmp($stmt->name->toString(), $shortName) !== 0) {
				continue;
			}
			$class = $stmt;
			break;
		}
		if ($class === null) {
			return false;
		}

		$scope = new ImportScope($namespace, $stmts);
		$class->attrGroups[] = new Node\AttributeGroup([$this->createAttribute($scope, $edit->attribute)]);

		$constructor = $class->getMethod('__construct');
		$isOneLine = $constructor !== null
			&& count($constructor->params) > 0
			&& $constructor->name->getStartLine() === $constructor->params[count($constructor->params) - 1]->getEndLine();
		foreach ($edit->parameterAttributes as $parameterName => $attribute) {
			if ($constructor === null) {
				throw new ShouldNotHappenException();
			}
			foreach ($constructor->params as $param) {
				if (!$param->var instanceof Node\Expr\Variable || $param->var->name !== $parameterName) {
					continue;
				}

				$param->attrGroups[] = new Node\AttributeGroup([$this->createAttribute($scope, $attribute)]);
				$param->setAttribute(AttributesPrinter::INLINE_ATTRIBUTES, $isOneLine);
			}
		}

		$stmts = $scope->stmts;

		return true;
	}

	private function createAttribute(ImportScope $scope, AttributeSpec $attribute): Node\Attribute
	{
		return new Node\Attribute($this->createName($scope, self::DI_NAMESPACE . $attribute->name), $this->createArgs($scope, $attribute));
	}

	/**
	 * @return list<Node\Arg>
	 */
	private function createArgs(ImportScope $scope, AttributeSpec $attribute): array
	{
		$args = [];
		foreach ($attribute->arguments as $name => $value) {
			$args[] = new Node\Arg($this->createExpr($scope, $value), name: new Node\Identifier($name));
		}

		return $args;
	}

	private function createExpr(ImportScope $scope, mixed $value): Node\Expr
	{
		if ($value instanceof AttributeSpec) {
			return new Node\Expr\New_($this->createName($scope, self::DI_NAMESPACE . $value->name), $this->createArgs($scope, $value));
		}
		if ($value instanceof ClassReference) {
			return new Node\Expr\ClassConstFetch($this->createName($scope, $value->className), 'class');
		}
		if (is_string($value)) {
			return new Node\Scalar\String_($value);
		}
		if (is_int($value)) {
			return new Node\Scalar\Int_($value);
		}
		if (is_bool($value)) {
			return new Node\Expr\ConstFetch(new Node\Name($value ? 'true' : 'false'));
		}
		if (is_array($value)) {
			$items = [];
			$isList = array_is_list($value);
			foreach ($value as $key => $item) {
				$items[] = new Node\ArrayItem(
					$this->createExpr($scope, $item),
					$isList ? null : $this->createExpr($scope, $key),
				);
			}

			return new Node\Expr\Array_($items, ['kind' => Node\Expr\Array_::KIND_SHORT]);
		}

		throw new ShouldNotHappenException();
	}

	/**
	 * Uses the short name and adds a use statement unless the short name already means another class in the file.
	 */
	private function createName(ImportScope $scope, string $className): Node\Name
	{
		$className = ltrim($className, '\\');
		$position = strrpos($className, '\\');
		$shortName = $position === false ? $className : substr($className, $position + 1);
		if ($className === '' || $shortName === '') {
			throw new ShouldNotHappenException(sprintf('Invalid class name %s.', $className));
		}
		$lowerShortName = strtolower($shortName);

		if (array_key_exists($lowerShortName, $scope->uses)) {
			return strcasecmp($scope->uses[$lowerShortName], $className) === 0
				? new Node\Name($shortName)
				: new Node\Name\FullyQualified($className);
		}

		$localName = $scope->namespace === '' ? $shortName : $scope->namespace . '\\' . $shortName;
		if (strcasecmp($localName, $className) === 0) {
			return new Node\Name($shortName);
		}
		if (array_key_exists($lowerShortName, $scope->unqualifiedNames)) {
			return new Node\Name\FullyQualified($className);
		}

		$scope->addUse($className, $lowerShortName);

		return new Node\Name($shortName);
	}

}
