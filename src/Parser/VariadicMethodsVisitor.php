<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Reflection\ParametersAcceptor;
use function array_key_exists;
use function array_pop;
use function count;
use function in_array;
use function sprintf;

final class VariadicMethodsVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'variadicMethods';

	public const ANONYMOUS_CLASS_PREFIX = 'class@anonymous';

	private ?Node $topNode = null;

	private ?string $inNamespace = null;

	/** @var array<string> */
	private array $classStack = [];

	private ?string $inMethod = null;

	/** @var array<string, array<string, true>> */
	private array $variadicMethods = [];

	public function beforeTraverse(array $nodes): ?array
	{
		$this->topNode = null;
		$this->variadicMethods = [];
		$this->inNamespace = null;
		$this->classStack = [];
		$this->inMethod = null;

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
				$className = sprintf('%s:%s:%s', self::ANONYMOUS_CLASS_PREFIX, $node->getStartLine(), $node->getEndLine());
				$this->classStack[] = $className;
			} else {
				$className = $node->name->name;
				$this->classStack[] = $this->inNamespace !== null ? $this->inNamespace . '\\' . $className : $className;
			}
		}

		if ($node instanceof ClassMethod) {
			$this->inMethod = $node->name->name;
		}

		if (
			$this->inMethod !== null
			&& $node instanceof Node\Expr\FuncCall
			&& $node->name instanceof Name
			&& in_array((string) $node->name, ParametersAcceptor::VARIADIC_FUNCTIONS, true)
		) {
			$lastClass = $this->classStack[count($this->classStack) - 1] ?? null;
			if ($lastClass !== null) {
				if (
					!array_key_exists($lastClass, $this->variadicMethods)
					|| !array_key_exists($this->inMethod, $this->variadicMethods[$lastClass])
				) {
					$this->variadicMethods[$lastClass][$this->inMethod] = true;
				}
			}

		}

		return null;
	}

	public function leaveNode(Node $node): ?Node
	{
		if ($node instanceof ClassMethod) {
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
