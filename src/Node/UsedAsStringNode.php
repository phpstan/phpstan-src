<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\NodeAbstract;

/**
 * Fired for every node whose value is used as a string. This covers both
 * values coerced to string and values stored into a string slot:
 *
 * - echo and print arguments, the operand of a (string) cast,
 * - string concatenation (`.` and `.=`), string interpolation/heredoc,
 * - inline HTML,
 * - the dynamic name of a property/method/constant access or a variable
 *   variable (`$foo->{$s}`, `$$s`, etc.),
 * - the value assigned to a variable when that value is a string,
 * - the value assigned to (or the default of) a native `string`-typed property.
 *
 * Concatenations and interpolated strings are reported once for the whole
 * expression instead of once per nested operand, so a rule can interpret the
 * built string as a single unit. When the assigned value already produces its
 * own node (a concatenation, interpolation or `(string)` cast), the enclosing
 * assignment does not report it a second time.
 *
 * The wrapped node is usually an expression, but for inline HTML it is the
 * original {@see \PhpParser\Node\Stmt\InlineHTML} statement, hence
 * {@see getNode()} returns a {@see NodeAbstract} rather than an
 * {@see \PhpParser\Node\Expr}.
 *
 * @api
 */
final class UsedAsStringNode extends NodeAbstract implements VirtualNode
{

	public function __construct(private NodeAbstract $node)
	{
		parent::__construct($node->getAttributes());
	}

	public function getNode(): NodeAbstract
	{
		return $this->node;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_UsedAsStringNode';
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
