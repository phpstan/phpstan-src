<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;

final class OriginalPropertyTypeExpr extends Expr implements VirtualNode
{

	public function __construct(private Expr\PropertyFetch|Expr\StaticPropertyFetch $propertyFetch)
	{
		parent::__construct([]);
	}

	public function getPropertyFetch(): Expr\PropertyFetch|Expr\StaticPropertyFetch
	{
		return $this->propertyFetch;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_OriginalPropertyTypeExpr';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
