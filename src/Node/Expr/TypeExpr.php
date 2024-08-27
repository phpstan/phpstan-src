<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Type\Type;

final class TypeExpr extends Expr implements VirtualNode
{

	public function __construct(private Type $exprType)
	{
		parent::__construct();
	}

	public function getExprType(): Type
	{
		return $this->exprType;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_TypeExpr';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
