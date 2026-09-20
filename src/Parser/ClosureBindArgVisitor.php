<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function count;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ClosureBindArgVisitor.cpp')]
final class ClosureBindArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'closureBindArg';

	public const PARAMETER_NAMES = ['closure', 'newThis', 'newScope'];

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
			$args = $node->getArgs();
			if (count($args) > 1) {
				$args = ArgumentPositionHelper::getArgsByPosition($args, self::PARAMETER_NAMES);
				if (isset($args[0])) {
					$args[0]->setAttribute(self::ATTRIBUTE_NAME, true);
				}
			}
		}
		return null;
	}

}
