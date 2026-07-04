<?php declare(strict_types = 1);

namespace PHPStan\Node\Property;

use ArrayAccess;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
use PHPStan\Node\Expr\UnsetOffsetExpr;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Type\ObjectType;

/**
 * @api
 */
final class PropertyWrite
{

	public function __construct(
		private PropertyFetch|StaticPropertyFetch $fetch,
		private Scope $scope,
		private bool $promotedPropertyWrite,
		private ClassPropertyNode|PropertyAssignNode|AssignRef|null $originalNode = null,
	)
	{
	}

	/**
	 * @return PropertyFetch|StaticPropertyFetch
	 */
	public function getFetch()
	{
		return $this->fetch;
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

	public function isPromotedPropertyWrite(): bool
	{
		return $this->promotedPropertyWrite;
	}

	/**
	 * Whether the write happens through offset access ($this->prop[...] = ...)
	 * on an ArrayAccess object, which goes through offsetSet() rather than
	 * reassigning the property itself.
	 */
	public function isViaOffsetAccess(): bool
	{
		if (!$this->originalNode instanceof PropertyAssignNode) {
			return false;
		}

		$assignedExpr = $this->originalNode->getAssignedExpr();
		if (
			!$assignedExpr instanceof SetOffsetValueTypeExpr
			&& !$assignedExpr instanceof SetExistingOffsetValueTypeExpr
			&& !$assignedExpr instanceof UnsetOffsetExpr
		) {
			return false;
		}

		return (new ObjectType(ArrayAccess::class))
			->isSuperTypeOf($this->scope->getType($this->originalNode->getPropertyFetch()))
			->yes();
	}

}
