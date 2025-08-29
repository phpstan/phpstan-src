<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\Cast\Void_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class VoidCastVisitor extends NodeVisitorAbstract
{

	private bool $pendingVoidCast = false;

	public const ATTRIBUTE_NAME = 'voidCastExpr';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Void_) {
			$this->pendingVoidCast = true;
		} elseif ($this->pendingVoidCast) {
			$node->setAttribute(self::ATTRIBUTE_NAME, true);
			$this->pendingVoidCast = false;
		}
		return null;
	}

}
