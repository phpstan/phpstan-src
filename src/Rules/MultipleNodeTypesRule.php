<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;

/**
 * A rule interested in several node classes whose only common ancestor is a broad one - Expr for
 * BinaryOp and AssignOp, Node for Stmt and PropertyHook. getNodeType() stays that ancestor, which
 * is what processNode() is typed against; the registry dispatches only the classes listed here, so
 * the rule is not called for every other node of the ancestor just to reject it.
 *
 * @api
 * @template TNodeType of Node
 * @extends Rule<TNodeType>
 */
interface MultipleNodeTypesRule extends Rule
{

	/**
	 * @return non-empty-list<class-string<TNodeType>>
	 */
	public function getNodeTypes(): array;

}
