<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ParametersAcceptor;
use function array_key_exists;
use function array_pop;
use function in_array;
use function sprintf;

#[AutowiredService]
final class VariadicMethodsVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'variadicMethods';

	public const ANONYMOUS_CLASS_PREFIX = 'class@anonymous';

	private ?Node $topNode = null;

	private ?string $inNamespace = null;

	/** @var array<string> */
	private array $classStack = [];

	/** @var array<string> */
	private array $inMethodStack = [];

	/** @var array<string, array<string, bool>> */
	public static array $cache = [];

	/** @var array<string, array<string, bool>> */
	private array $variadicMethods = [];

	#[Override]
	public function beforeTraverse(array $nodes): ?array
	{
		$this->topNode = null;
		$this->variadicMethods = [];
		$this->inNamespace = null;
		$this->classStack = [];
		$this->inMethodStack = [];

		return null;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		$this->topNode ??= $node;

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
			$this->inMethodStack[] = $node->name->name;
		}

		$lastMethod = array_last($this->inMethodStack) ?? null;

		if (
			$lastMethod !== null
			&& $node instanceof Node\Expr\FuncCall
			&& $node->name instanceof Name
			&& in_array((string) $node->name, ParametersAcceptor::VARIADIC_FUNCTIONS, true)
		) {
			$lastClass = array_last($this->classStack) ?? null;
			if ($lastClass !== null) {
				if (
					!array_key_exists($lastClass, $this->variadicMethods)
					|| !array_key_exists($lastMethod, $this->variadicMethods[$lastClass])
				) {
					$this->variadicMethods[$lastClass][$lastMethod] = true;
				}
			}

		}

		return null;
	}

	#[Override]
	public function leaveNode(Node $node): ?Node
	{
		if ($node instanceof ClassMethod) {
			$lastClass = array_last($this->classStack) ?? null;
			$lastMethod = array_last($this->inMethodStack) ?? null;
			if ($lastClass !== null && $lastMethod !== null) {
				$this->variadicMethods[$lastClass][$lastMethod] ??= false;
			}
			array_pop($this->inMethodStack);
		}

		if ($node instanceof Node\Stmt\ClassLike) {
			array_pop($this->classStack);
		}

		if ($node instanceof Node\Stmt\Namespace_ && $node->name !== null) {
			$this->inNamespace = null;
		}

		return null;
	}

	#[Override]
	public function afterTraverse(array $nodes): ?array
	{
		if ($this->topNode !== null && $this->variadicMethods !== []) {
			$filteredMethods = [];
			foreach ($this->variadicMethods as $class => $methods) {
				foreach ($methods as $name => $variadic) {
					self::$cache[$class][$name] = $variadic;
					if (!$variadic) {
						continue;
					}

					$filteredMethods[$class][$name] = true;
				}
			}
			$this->topNode->setAttribute(self::ATTRIBUTE_NAME, $filteredMethods);
		}

		return null;
	}

}
