<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Type\Type;

final class AlwaysRememberedExpr extends Expr implements VirtualNode
{

	public function __construct(public Expr $expr, private Type $type, private Type $nativeType)
	{
		parent::__construct([]);
	}

	public function getExpr(): Expr
	{
		return $this->expr;
	}

	public function getExprType(): Type
	{
		return $this->type;
	}

	public function getNativeExprType(): Type
	{
		return $this->nativeType;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_AlwaysRememberedExpr';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return ['expr'];
	}

}
