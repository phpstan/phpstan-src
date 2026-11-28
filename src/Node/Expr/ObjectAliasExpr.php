<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Turbo\ReferencedByTurboExtension;

/**
 * Remembers that two expressions (a variable and a variable or a property fetch chain)
 * hold the same object, so a property write or invalidation through one of them
 * reaches the facts remembered about the other one.
 */
#[ReferencedByTurboExtension(key: 'objectAliasExpr')]
final class ObjectAliasExpr extends Expr implements VirtualNode
{

	public function __construct(public Expr $expr, public Expr $aliasedExpr)
	{
		parent::__construct([]);
	}

	public function getExpr(): Expr
	{
		return $this->expr;
	}

	public function getAliasedExpr(): Expr
	{
		return $this->aliasedExpr;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_ObjectAliasExpr';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return ['expr', 'aliasedExpr'];
	}

}
