<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Type\Type;

class PropertyAssignNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private Expr\PropertyFetch|Expr\StaticPropertyFetch $propertyFetch,
		private Type $assignedType,
	)
	{
		parent::__construct($propertyFetch->getAttributes());
	}

	public function getPropertyFetch(): Expr\PropertyFetch|Expr\StaticPropertyFetch
	{
		return $this->propertyFetch;
	}

	public function getAssignedType(): Type
	{
		return $this->assignedType;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_PropertyAssignNodeNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
