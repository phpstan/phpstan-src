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
use function count;
use function implode;
use function in_array;
use function sprintf;
use function str_contains;

final class VariadicMethodsVisitor extends NodeVisitorAbstract
{

	private ?Node $topNode = null;

	private ?string $inNamespace = null;

	private ?string $inClassLike = null;

	/** @var array<string> */
	private array $classStack = [];

	private ?string $inMethod = null;

	/** @var array<string, array<string, TrinaryLogic>> */
	private array $variadicMethods = [];

	public const ATTRIBUTE_NAME = 'variadicMethods';

	private const ANONYMOUS_CLASS_PREFIX = 'class@anonymous';

	public function beforeTraverse(array $nodes): ?array
	{
		$this->topNode = null;
		$this->variadicMethods = [];
		$this->inNamespace = null;
		$this->classStack = [];
		$this->inClassLike = null;
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
				$this->inClassLike = $className; // anonymous classes are in global namespace
			} else {
				$className = $node->name->name;
				$this->classStack[] = $className;
				$this->inClassLike = $this->inNamespace !== null ? $this->inNamespace . '\\' . implode('\\', $this->classStack) : implode('\\', $this->classStack);
			}

			$this->variadicMethods[$this->inClassLike] ??= [];
		}

		if ($this->inClassLike !== null && $node instanceof ClassMethod) {
			$this->inMethod = $node->name->name;
		}

		if (
			$this->inClassLike !== null
			&& $this->inMethod !== null
			&& $node instanceof Node\Expr\FuncCall
			&& $node->name instanceof Name
			&& in_array((string) $node->name, ParametersAcceptor::VARIADIC_FUNCTIONS, true)
		) {
			if (!array_key_exists($this->inMethod, $this->variadicMethods[$this->inClassLike])) {
				$this->variadicMethods[$this->inClassLike][$this->inMethod] = TrinaryLogic::createYes();
			} else {
				$this->variadicMethods[$this->inClassLike][$this->inMethod]->and(TrinaryLogic::createYes());
			}

		}

		return null;
	}

	public function leaveNode(Node $node): ?Node
	{
		if (
			$node instanceof ClassMethod
			&& $this->inClassLike !== null
		) {
			$this->variadicMethods[$this->inClassLike][$node->name->name] ??= TrinaryLogic::createNo();
			$this->inMethod = null;
		}

		if ($node instanceof Node\Stmt\ClassLike) {
			array_pop($this->classStack);

			if ($this->classStack !== []) {
				$lastClass = $this->classStack[count($this->classStack) - 1];

				if (str_contains($lastClass, self::ANONYMOUS_CLASS_PREFIX)) {
					$this->inClassLike = $lastClass;
				} else {
					$this->inClassLike = $this->inNamespace !== null ? $this->inNamespace . '\\' . implode('\\', $this->classStack) : implode('\\', $this->classStack);
				}
			} else {
				$this->inClassLike = null;
			}
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
