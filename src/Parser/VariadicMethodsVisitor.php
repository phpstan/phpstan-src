<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Reflection\ParametersAcceptor;
use function array_key_exists;

final class VariadicMethodsVisitor extends NodeVisitorAbstract
{

	private ?Node $topNode = null;

	private ?string $inClassLike = null;

	private ?string $inNamespace = null;

	/** @var array<string, list<string>> */
	private array $variadicMethods = [];

	public const ATTRIBUTE_NAME = 'variadicMethods';

	public function __construct(
		private FunctionCallStatementFinder $functionCallStatementFinder,
	)
	{
	}

	public function beforeTraverse(array $nodes): ?array
	{
		$this->topNode = null;
		$this->variadicMethods = [];
		$this->inClassLike = null;
		$this->inNamespace = null;

		return null;
	}

	public function enterNode(Node $node): ?Node
	{
		if ($this->topNode === null) {
			$this->topNode = $node;
		}

		if ($node instanceof Node\Stmt\Namespace_ && $node->name !== null) {
			$this->inNamespace = $node->name->toString();
		}

		if ($node instanceof Node\Stmt\ClassLike && $node->name instanceof Node\Identifier) {
			$this->inClassLike = $node->name->name;
		}

		if ($this->inClassLike !== null && $node instanceof ClassMethod) {
			if ($node->getStmts() === null) {
				return null; // interface
			}

			if ($this->functionCallStatementFinder->findFunctionCallInStatements(ParametersAcceptor::VARIADIC_FUNCTIONS, $node->getStmts()) !== null) {
				$className = $this->inNamespace !== null ? $this->inNamespace . '\\' . $this->inClassLike : $this->inClassLike;
				if (!array_key_exists($className, $this->variadicMethods)) {
					$this->variadicMethods[$className] = [];
				}
				$this->variadicMethods[$className][] = $node->name->name;
			}
		}

		return null;
	}

	public function leaveNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt\Namespace_ && $node->name !== null) {
			$this->inNamespace = null;
		}

		if ($node instanceof Node\Stmt\ClassLike && $node->name instanceof Node\Identifier) {
			$this->inClassLike = null;
		}

		return null;
	}

	public function afterTraverse(array $nodes): ?array
	{
		if ($this->topNode !== null && $this->variadicMethods !== []) {
			$this->topNode->setAttribute(self::ATTRIBUTE_NAME, $this->variadicMethods);
		}

		return null;
	}

}
