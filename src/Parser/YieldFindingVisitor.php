<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

/**
 * Collects all `yield`/`yield from` expressions that belong to a single function-like
 * (a function/method/closure makes itself a generator), regardless of whether they are
 * reachable. Nested function-likes are not descended into, because a `yield` inside a
 * nested closure makes the nested closure a generator, not the outer scope.
 */
final class YieldFindingVisitor extends NodeVisitorAbstract
{

	/** @var list<Yield_|YieldFrom> */
	private array $yieldNodes = [];

	#[Override]
	public function enterNode(Node $node): ?int
	{
		if ($node instanceof Node\FunctionLike) {
			return NodeVisitor::DONT_TRAVERSE_CHILDREN;
		}

		if ($node instanceof Yield_ || $node instanceof YieldFrom) {
			$this->yieldNodes[] = $node;
		}

		return null;
	}

	/**
	 * @param Node[] $nodes
	 * @return list<Yield_|YieldFrom>
	 */
	public static function findInNodes(array $nodes): array
	{
		$visitor = new self();
		$traverser = new NodeTraverser($visitor);
		$traverser->traverse($nodes);

		return $visitor->yieldNodes;
	}

}
