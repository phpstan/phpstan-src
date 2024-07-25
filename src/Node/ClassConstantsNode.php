<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeAbstract;
use PHPStan\Node\Constant\ClassConstantFetch;
use PHPStan\Reflection\ClassReflection;

/**
 * @api
 * @final
 */
class ClassConstantsNode extends NodeAbstract implements VirtualNode
{

	/**
	 * @param ClassConst[] $constants
	 * @param ClassConstantFetch[] $fetches
	 */
	public function __construct(private ClassLike $class, private array $constants, private array $fetches, private ClassReflection $classReflection)
	{
		parent::__construct($class->getAttributes());
	}

	public function getClass(): ClassLike
	{
		return $this->class;
	}

	/**
	 * @return ClassConst[]
	 */
	public function getConstants(): array
	{
		return $this->constants;
	}

	/**
	 * @return ClassConstantFetch[]
	 */
	public function getFetches(): array
	{
		return $this->fetches;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_ClassConstantsNode';
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
