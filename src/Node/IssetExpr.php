<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;

/**
 * @api
 */
final class IssetExpr extends Expr implements VirtualNode
{

	/**
	 * @api
	 */
	public function __construct(
		private Expr $expr,
	)
	{
		parent::__construct([]);
	}

	public function getExpr(): Expr
	{
		return $this->expr;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_IssetExpr';
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
