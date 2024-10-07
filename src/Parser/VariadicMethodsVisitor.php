<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use function array_key_exists;
use function array_pop;
use function implode;
use function in_array;
use function sprintf;

final class VariadicMethodsVisitor extends NodeVisitorAbstract
{

	private ?Node $topNode = null;

	private ?string $inNamespace = null;

	/** @var array<string> */
	private array $classStack = [];

	private ?string $inMethod = null;

	/** @var array<string, array<string, TrinaryLogic>> */
	private array $variadicMethods = [];

	private int $anonymousClassIndex = 0;

	public const ATTRIBUTE_NAME = 'variadicMethods';

	public function beforeTraverse(array $nodes): ?array
	{
		$this->topNode = null;
		$this->variadicMethods = [];
		$this->inNamespace = null;
		$this->classStack = [];
		$this->inMethod = null;
		$this->anonymousClassIndex = 0;

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

		if ($node instanceof Node\Stmt\ClassLike) {
			if (!$node->name instanceof Node\Identifier) {
				$className = sprintf('class@anonymous:%s:%s', $node->getStartLine(), ++$this->anonymousClassIndex);
			} else {
				$className = $node->name->name;
			}

			$this->classStack[] = $className;
			$inClassLike = $this->inNamespace !== null ? $this->inNamespace . '\\' . implode('\\', $this->classStack) : implode('\\', $this->classStack);
			$this->variadicMethods[$inClassLike] ??= [];
		}

		if ($this->classStack !== [] && $node instanceof ClassMethod) {
			$this->inMethod = $node->name->name;
		}

		if (
			$this->classStack !== []
			&& $this->inMethod !== null
			&& $node instanceof Node\Expr\FuncCall
			&& $node->name instanceof Name
			&& in_array((string) $node->name, ParametersAcceptor::VARIADIC_FUNCTIONS, true)
		) {
			$inClassLike = $this->inNamespace !== null ? $this->inNamespace . '\\' . implode('\\', $this->classStack) : implode('\\', $this->classStack);

			if (!array_key_exists($this->inMethod, $this->variadicMethods[$inClassLike])) {
				$this->variadicMethods[$inClassLike][$this->inMethod] = TrinaryLogic::createYes();
			} else {
				$this->variadicMethods[$inClassLike][$this->inMethod]->and(TrinaryLogic::createYes());
			}

		}

		return null;
	}

	public function leaveNode(Node $node): ?Node
	{
		if (
			$node instanceof ClassMethod
			&& $this->classStack !== []
		) {
			$inClassLike = $this->inNamespace !== null ? $this->inNamespace . '\\' . implode('\\', $this->classStack) : implode('\\', $this->classStack);

			$this->variadicMethods[$inClassLike][$node->name->name] ??= TrinaryLogic::createNo();
			$this->inMethod = null;
		}

		if ($node instanceof Node\Stmt\ClassLike) {
			array_pop($this->classStack);
		}

		if ($node instanceof Node\Stmt\Namespace_ && $node->name !== null) {
			$this->inNamespace = null;
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
