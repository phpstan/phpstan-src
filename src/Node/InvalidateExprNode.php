<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;

/**
 * @api
 */
#[ReferencedByTurboExtension(key: 'invalidateExprNode')]
final class InvalidateExprNode extends NodeAbstract implements VirtualNode
{

	public function __construct(private Expr $expr)
	{
		parent::__construct($expr->getAttributes());
	}

	public function getExpr(): Expr
	{
		return $this->expr;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_InvalidateExpr';
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
