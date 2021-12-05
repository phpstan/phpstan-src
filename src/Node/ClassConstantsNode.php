<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeAbstract;
use PHPStan\Node\Constant\ClassConstantFetch;

/** @api */
class ClassConstantsNode extends NodeAbstract implements VirtualNode
{

	private ClassLike $class;

	/** @var ClassConst[] */
	private array $constants;

	/** @var ClassConstantFetch[] */
	private array $fetches;

	/**
	 * @param ClassConst[] $constants
	 * @param ClassConstantFetch[] $fetches
	 */
	public function __construct(ClassLike $class, array $constants, array $fetches)
	{
		parent::__construct($class->getAttributes());
		$this->class = $class;
		$this->constants = $constants;
		$this->fetches = $fetches;
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
		return 'PHPStan_Node_ClassPropertiesNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
