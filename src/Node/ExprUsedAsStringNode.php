<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;

/**
 * Fired for every expression whose value is used as a string: echo and print
 * arguments, the operand of a (string) cast, string concatenation (`.` and
 * `.=`), string interpolation/heredoc and inline HTML.
 *
 * Concatenations and interpolated strings are reported once for the whole
 * expression instead of once per nested operand, so a rule can interpret the
 * built string as a single unit.
 *
 * @api
 */
final class ExprUsedAsStringNode extends NodeAbstract implements VirtualNode
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
		return 'PHPStan_Node_ExprUsedAsStringNode';
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
