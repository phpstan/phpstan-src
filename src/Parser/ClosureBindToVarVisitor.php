<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ClosureBindToVarVisitor.cpp')]
final class ClosureBindToVarVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'closureBindToVar';

	public const PARAMETER_NAMES = ['newThis', 'newScope'];

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (
			$node instanceof Node\Expr\MethodCall
			&& $node->name instanceof Identifier
			&& $node->name->toLowerString() === 'bindto'
			&& !$node->isFirstClassCallable()
		) {
			$args = ArgumentPositionHelper::getArgsByPosition($node->getArgs(), self::PARAMETER_NAMES);
			if (isset($args[0])) {
				$args[0]->setAttribute(self::ATTRIBUTE_NAME, $node->var);
			}
		}
		return null;
	}

}
