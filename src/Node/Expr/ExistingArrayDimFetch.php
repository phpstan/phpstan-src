<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;

final class ExistingArrayDimFetch extends Expr implements VirtualNode
{

	public function __construct(private Expr $var, private Expr $dim)
	{
		parent::__construct([]);
	}

	public function getVar(): Expr
	{
		return $this->var;
	}

	public function getDim(): Expr
	{
		return $this->dim;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_ExistingArrayDimFetch';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
