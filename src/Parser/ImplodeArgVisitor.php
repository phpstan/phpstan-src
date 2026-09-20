<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function in_array;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ImplodeArgVisitor.cpp')]
final class ImplodeArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isImplodeArg';

	public const PARAMETER_NAMES = ['separator', 'array'];

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && !$node->isFirstClassCallable()) {
			$functionName = $node->name->toLowerString();
			if (in_array($functionName, ['implode', 'join'], true)) {
				$args = ArgumentPositionHelper::getArgsByPosition($node->getArgs(), self::PARAMETER_NAMES);
				// implode(array: $a) leaves the first parameter unfilled
				$markedArg = $args[0] ?? $args[1] ?? null;
				if ($markedArg !== null) {
					$markedArg->setAttribute(self::ATTRIBUTE_NAME, true);
				}
			}
		}
		return null;
	}

}
