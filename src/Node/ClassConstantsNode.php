<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeAbstract;
use PHPStan\Node\Constant\ClassConstantFetch;
use PHPStan\Reflection\ClassReflection;
use function array_map;

/**
 * @api
 */
final class ClassConstantsNode extends NodeAbstract implements VirtualNode
{

	/**
	 * @param ClassConstant[] $constants
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
		return array_map(static fn (ClassConstant $constant) => $constant->getNode(), $this->constants);
	}

	/**
	 * @return ClassConstant[]
	 */
	public function getClassConstants(): array
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

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_ClassConstantsNode';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

	public function getClassReflection(): ClassReflection
	{
		return $this->classReflection;
	}

}
