<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parser\Parser;
use Throwable;
use function array_key_exists;
use function count;
use function is_array;
use function strtolower;

/**
 * Determines whether a function or method has an empty body purely from its source file.
 *
 * An empty body provably has no impure points and no throw points, so it lets the
 * "no effect" dead-code rules report calls to functions/methods whose source is not
 * part of the analysed file set (e.g. coming from a third-party dependency), where the
 * analysis-based *WithoutImpurePointsCollector collectors never run.
 *
 * Uses a parser that keeps function/method bodies intact - the default analysis parser
 * strips bodies of non-analysed files, which would make every non-analysed callable look empty.
 */
#[AutowiredService]
final class EmptyBodyCallableDetector
{

	/** @var array<string, Node[]|null> */
	private array $parsedFiles = [];

	public function __construct(
		#[AutowiredParameter(ref: '@currentPhpVersionRichParser')]
		private Parser $parser,
	)
	{
	}

	public function hasEmptyFunctionBody(?string $fileName, string $functionName): bool
	{
		$nodes = $this->parseFile($fileName);
		if ($nodes === null) {
			return false;
		}

		$functionNode = $this->findFunctionNode($functionName, $nodes);
		if ($functionNode === null) {
			return false;
		}

		return $this->hasEmptyBody($functionNode);
	}

	public function hasEmptyMethodBody(?string $fileName, string $className, string $methodName): bool
	{
		$nodes = $this->parseFile($fileName);
		if ($nodes === null) {
			return false;
		}

		$classNode = $this->findClassNode($className, $nodes);
		if ($classNode === null) {
			return false;
		}

		$methodNode = $this->findMethodNode($methodName, $classNode->stmts);
		if ($methodNode === null) {
			return false;
		}

		return $this->hasEmptyBody($methodNode);
	}

	/**
	 * @return Node[]|null
	 */
	private function parseFile(?string $fileName): ?array
	{
		if ($fileName === null) {
			return null;
		}

		if (array_key_exists($fileName, $this->parsedFiles)) {
			return $this->parsedFiles[$fileName];
		}

		try {
			return $this->parsedFiles[$fileName] = $this->parser->parseFile($fileName);
		} catch (Throwable) {
			return $this->parsedFiles[$fileName] = null;
		}
	}

	private function hasEmptyBody(ClassMethod|Function_ $node): bool
	{
		if ($node->stmts === null || count($node->stmts) !== 0) {
			return false;
		}

		foreach ($node->params as $param) {
			// promoted properties assign to $this, by-reference params create new variables
			if ($param->flags !== 0 || $param->byRef) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param Node[] $nodes
	 */
	private function findFunctionNode(string $functionName, array $nodes): ?Function_
	{
		foreach ($nodes as $node) {
			if (
				$node instanceof Function_
				&& $node->namespacedName !== null
				&& strtolower($node->namespacedName->toString()) === strtolower($functionName)
			) {
				return $node;
			}
			if (
				!$node instanceof Namespace_
				&& !$node instanceof Declare_
			) {
				continue;
			}
			$result = $this->findFunctionNode($functionName, $this->getChildStatements($node));
			if ($result !== null) {
				return $result;
			}
		}
		return null;
	}

	/**
	 * @param Node[] $nodes
	 */
	private function findClassNode(string $className, array $nodes): ?Class_
	{
		foreach ($nodes as $node) {
			if (
				$node instanceof Class_
				&& $node->namespacedName !== null
				&& $node->namespacedName->toString() === $className
			) {
				return $node;
			}
			if (
				!$node instanceof Namespace_
				&& !$node instanceof Declare_
			) {
				continue;
			}
			$result = $this->findClassNode($className, $this->getChildStatements($node));
			if ($result !== null) {
				return $result;
			}
		}
		return null;
	}

	/**
	 * @param Node\Stmt[] $classStatements
	 */
	private function findMethodNode(string $methodName, array $classStatements): ?ClassMethod
	{
		foreach ($classStatements as $statement) {
			if (
				$statement instanceof ClassMethod
				&& strtolower($statement->name->toString()) === strtolower($methodName)
			) {
				return $statement;
			}
		}
		return null;
	}

	/**
	 * @return Node[]
	 */
	private function getChildStatements(Namespace_|Declare_ $node): array
	{
		$statements = [];
		foreach ($node->getSubNodeNames() as $subNodeName) {
			$subNode = $node->{$subNodeName};
			if (!is_array($subNode)) {
				$subNode = [$subNode];
			}
			foreach ($subNode as $childNode) {
				if (!$childNode instanceof Node) {
					continue;
				}
				$statements[] = $childNode;
			}
		}
		return $statements;
	}

}
