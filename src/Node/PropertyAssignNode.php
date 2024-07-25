<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;

final class PropertyAssignNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private Expr\PropertyFetch|Expr\StaticPropertyFetch $propertyFetch,
		private Expr $assignedExpr,
		private bool $assignOp,
	)
	{
		parent::__construct($propertyFetch->getAttributes());
	}

	public function getPropertyFetch(): Expr\PropertyFetch|Expr\StaticPropertyFetch
	{
		return $this->propertyFetch;
	}

	public function getAssignedExpr(): Expr
	{
		return $this->assignedExpr;
	}

	public function isAssignOp(): bool
	{
		return $this->assignOp;
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
