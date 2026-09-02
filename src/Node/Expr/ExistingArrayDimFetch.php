<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;

/**
 * The chain links reference the original, already-processed AST nodes, so
 * consumers read their stored results instead of re-walking. No results are
 * carried here: these wrappers end up inside scope-held synthetic expressions,
 * and a carried result would pin its whole scope graph.
 */
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

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_ExistingArrayDimFetch';
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
