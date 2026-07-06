<?php declare(strict_types = 1);

namespace PHPStan\Node;

use ArrayAccess;
use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
use PHPStan\Node\Expr\UnsetOffsetExpr;
use PHPStan\Type\ObjectType;

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

	/**
	 * Whether the assignment is an offset write ($this->prop[...] = ...,
	 * unset($this->prop[...])) on an ArrayAccess object, which goes through
	 * offsetSet()/offsetUnset() rather than reassigning the property itself.
	 */
	public function isArrayAccessOffsetWrite(Scope $scope): bool
	{
		if (
			!$this->assignedExpr instanceof SetOffsetValueTypeExpr
			&& !$this->assignedExpr instanceof SetExistingOffsetValueTypeExpr
			&& !$this->assignedExpr instanceof UnsetOffsetExpr
		) {
			return false;
		}

		return (new ObjectType(ArrayAccess::class))
			->isSuperTypeOf($scope->getType($this->assignedExpr->getVar()))
			->yes();
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_PropertyAssignNodeNode';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}
