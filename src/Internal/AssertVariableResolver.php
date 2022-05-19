<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\ShouldNotHappenException;
use function count;
use function is_string;

class AssertVariableResolver
{

	/**
	 * @param callable(string): ?Node\Expr $cb
	 */
	public static function map(Node\Expr $expr, callable $cb): Node\Expr
	{
		$traverser = new NodeTraverser();
		$traverser->addVisitor(new class ($cb) extends NodeVisitorAbstract {

			/** @var callable(string): ?Node\Expr */
			private $cb;

			/**
			 * @param callable(string): ?Node\Expr $cb
			 */
			public function __construct(callable $cb)
			{
				$this->cb = $cb;
			}

			public function leaveNode(Node $node): ?Node\Expr
			{
				if ($node instanceof Node\Expr\Variable && is_string($node->name)) {
					$replacement = ($this->cb)($node->name);
					if ($replacement !== null) {
						return $replacement;
					}
				}

				return null;
			}

		});

		$nodes = $traverser->traverse([$expr]);

		if (count($nodes) !== 1) {
			throw new ShouldNotHappenException();
		}

		if (!$nodes[0] instanceof Node\Expr) {
			throw new ShouldNotHappenException();
		}

		return $nodes[0];
	}

}
