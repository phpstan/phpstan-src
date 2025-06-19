<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;

final class UnsetOffsetExpr extends Expr implements VirtualNode
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

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_UnsetOffsetExpr';
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
