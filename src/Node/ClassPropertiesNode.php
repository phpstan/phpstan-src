<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeAbstract;
use PHPStan\Node\Method\MethodCall;
use PHPStan\Node\Property\PropertyRead;
use PHPStan\Node\Property\PropertyWrite;

class ClassPropertiesNode extends NodeAbstract implements VirtualNode
{

	private ClassLike $class;

	/** @var Property[] */
	private array $properties;

	/** @var array<int, PropertyRead|PropertyWrite> */
	private array $propertyUsages;

	/** @var array<int, MethodCall> */
	private array $methodCalls;

	/**
	 * @param ClassLike $class
	 * @param Property[] $properties
	 * @param array<int, PropertyRead|PropertyWrite> $propertyUsages
	 * @param array<int, MethodCall> $methodCalls
	 */
	public function __construct(ClassLike $class, array $properties, array $propertyUsages, array $methodCalls)
	{
		parent::__construct($class->getAttributes());
		$this->class = $class;
		$this->properties = $properties;
		$this->propertyUsages = $propertyUsages;
		$this->methodCalls = $methodCalls;
	}

	public function getClass(): ClassLike
	{
		return $this->class;
	}

	/**
	 * @return Property[]
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	/**
	 * @return array<int, PropertyRead|PropertyWrite>
	 */
	public function getPropertyUsages(): array
	{
		return $this->propertyUsages;
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
