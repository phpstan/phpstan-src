<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ImmediatelyInvokedClosureVisitor.cpp')]
final class ImmediatelyInvokedClosureVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isImmediatelyInvokedClosure';
	public const ARGS_ATTRIBUTE_NAME = 'immediatelyInvokedClosureArgs';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (
			$node instanceof Node\Expr\FuncCall
			&& ($node->name instanceof Node\Expr\Closure || $node->name instanceof Node\Expr\ArrowFunction)
			&& !$node->isFirstClassCallable()
		) {
			$node->name->setAttribute(self::ATTRIBUTE_NAME, true);
			$node->name->setAttribute(self::ARGS_ATTRIBUTE_NAME, $node->args);
		}

		return null;
	}

}
