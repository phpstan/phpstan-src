<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\NodeVisitor;

/**
 * One change of a fixable error, registered with RuleErrorBuilder::fixNode() or removeNode().
 */
final class NodeFix
{

	/**
	 * @param callable(Node): (Node|NodeVisitor::REMOVE_NODE) $callback
	 * @param string|null $file the file of the node, null for the file of the error
	 */
	public function __construct(
		public readonly Node $node,
		public readonly mixed $callback,
		public readonly ?string $file,
	)
	{
	}

}
