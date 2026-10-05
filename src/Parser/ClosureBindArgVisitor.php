<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ClosureBindArgVisitor.cpp')]
final class ClosureBindArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'closureBindArg';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (
			$node instanceof Node\Expr\StaticCall
			&& $node->class instanceof Node\Name
			&& $node->class->toLowerString() === 'closure'
			&& $node->name instanceof Identifier
			&& $node->name->toLowerString() === 'bind'
			&& !$node->isFirstClassCallable()
		) {
			$closureArg = null;
			$newThisArg = null;
			foreach ($node->getArgs() as $i => $arg) {
				if ($arg->name === null) {
					if ($i === 0) {
						$closureArg = $arg;
					} elseif ($i === 1) {
						$newThisArg = $arg;
					}
					continue;
				}

				// a duplicate named argument does not replace the one already
				// found, like in ArgumentsNormalizer::reorderArgs()
				if ($arg->name->toString() === 'closure') {
					$closureArg ??= $arg;
				} elseif ($arg->name->toString() === 'newThis') {
					$newThisArg ??= $arg;
				}
			}

			if ($closureArg !== null && $newThisArg !== null) {
				$closureArg->setAttribute(self::ATTRIBUTE_NAME, true);
			}
		}

		return null;
	}

}
