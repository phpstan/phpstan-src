<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15378;

use function PHPStan\Testing\assertType;

/**
 * @template TNode of object
 */
class NodeCursor {
	/**
	 * @var static<ANode>
	 */
	public ?self $a = null;

	/**
	 * @var static
	 */
	public ?self $b = null;

	final public function __construct(
		/**
		 * @var TNode
		 */
		public readonly object $node,
	) {
		// empty
	}
}

class ANode {

}

class BNode {

}

$node   = new BNode();
$cursor = new NodeCursor($node);

assertType('Bug15378\NodeCursor<Bug15378\ANode>|null', $cursor->a);
assertType('Bug15378\NodeCursor<Bug15378\BNode>|null', $cursor->b);
