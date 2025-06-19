<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class StandaloneThrowExprVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'standaloneThrowExpr';

	#[Override]
	public function enterNode(Node $node): ?Node\Stmt\Expression
	{
		if (!$node instanceof Node\Stmt\Expression) {
			return null;
		}

		if (!$node->expr instanceof Node\Expr\Throw_) {
			return null;
		}

		$node->expr->setAttribute(self::ATTRIBUTE_NAME, true);

		return $node;
	}

}
