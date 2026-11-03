<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Internal\TokenStream;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileReader;
use PHPStan\Fixable\NewClassNamesVisitor;
use PHPStan\Fixable\PhpPrinter;
use PHPStan\Fixable\PhpPrinterIndentationDetectorVisitor;
use PHPStan\ShouldNotHappenException;
use function array_is_list;
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

		$nameResolver = new NameResolver(new Collecting(), ['replaceNodes' => false]);
		/** @var list<Node\Stmt> $newStmts */
		$newStmts = (new NodeTraverser(
			$nameResolver,
			new NewClassNamesVisitor($nameResolver->getNameContext(), importNames: true, preferSelf: false),
		))->traverse($newStmts);

		return (new AttributesPrinter(['indent' => $indent]))->printFormatPreserving($newStmts, $oldStmts, $tokens);
	}

	/**
	 * @param list<Node\Stmt> $stmts
	 */
	private function addAttributes(string $file, array $stmts, ClassAttributes $edit): void
	{
		$className = $edit->reflection->getName();
		$namespace = $edit->reflection->getNamespaceName();
		$shortName = $edit->reflection->getShortName();

		if ($namespace === '') {
			$this->addAttributesInScope($stmts, $shortName, $edit);
			return;
		}

		foreach ($stmts as $stmt) {
			if (!$stmt instanceof Node\Stmt\Namespace_ || $stmt->name === null || strcasecmp($stmt->name->toString(), $namespace) !== 0) {
				continue;
			}
			if ($this->addAttributesInScope($stmt->stmts, $shortName, $edit)) {
				return;
			}
		}

		throw new ShouldNotHappenException(sprintf('Class %s not found in %s.', $className, $file));
	}

	/**
	 * @param list<Node\Stmt> $stmts statements of a namespace, or of a file without one
	 */
	private function addAttributesInScope(array $stmts, string $shortName, ClassAttributes $edit): bool
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

		$class->attrGroups[] = new Node\AttributeGroup([$this->createAttribute($edit->attribute)]);

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

				$param->attrGroups[] = new Node\AttributeGroup([$this->createAttribute($attribute)]);
				$param->setAttribute(AttributesPrinter::INLINE_ATTRIBUTES, $isOneLine);
			}
		}

		return true;
	}

	private function createAttribute(AttributeSpec $attribute): Node\Attribute
	{
		return new Node\Attribute(new Node\Name\FullyQualified(self::DI_NAMESPACE . $attribute->name), $this->createArgs($attribute));
	}

	/**
	 * @return list<Node\Arg>
	 */
	private function createArgs(AttributeSpec $attribute): array
	{
		$args = [];
		foreach ($attribute->arguments as $name => $value) {
			$args[] = new Node\Arg($this->createExpr($value), name: new Node\Identifier($name));
		}

		return $args;
	}

	private function createExpr(mixed $value): Node\Expr
	{
		if ($value instanceof AttributeSpec) {
			return new Node\Expr\New_(new Node\Name\FullyQualified(self::DI_NAMESPACE . $value->name), $this->createArgs($value));
		}
		if ($value instanceof ClassReference) {
			$className = ltrim($value->className, '\\');
			if ($className === '') {
				throw new ShouldNotHappenException('Empty class name.');
			}

			return new Node\Expr\ClassConstFetch(new Node\Name\FullyQualified($className), 'class');
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
					$this->createExpr($item),
					$isList ? null : $this->createExpr($key),
				);
			}

			return new Node\Expr\Array_($items, ['kind' => Node\Expr\Array_::KIND_SHORT]);
		}

		throw new ShouldNotHappenException();
	}

}
