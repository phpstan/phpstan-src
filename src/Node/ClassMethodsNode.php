<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeAbstract;
use PHPStan\Node\Method\MethodCall;
use PHPStan\Reflection\ClassReflection;

/** @api */
class ClassMethodsNode extends NodeAbstract implements VirtualNode
{

	/**
	 * @param ClassMethod[] $methods
	 * @param array<int, MethodCall> $methodCalls
	 */
	public function __construct(private ClassLike $class, private array $methods, private array $methodCalls, private ClassReflection $classReflection)
	{
		parent::__construct($class->getAttributes());
	}

	public function getClass(): ClassLike
	{
		return $this->class;
	}

	/**
	 * @return ClassMethod[]
	 */
	public function getMethods(): array
	{
		return $this->methods;
	}

	/**
	 * @return array<int, MethodCall>
	 */
	public function getMethodCalls(): array
	{
		return $this->methodCalls;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_ClassMethodsNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

	public function getClassReflection(): ClassReflection
	{
		return $this->classReflection;
	}

}
