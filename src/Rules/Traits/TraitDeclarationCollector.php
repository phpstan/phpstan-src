<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\RegisteredCollector;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @implements Collector<Node\Stmt\Trait_, array{string, int}>
 */
#[RegisteredCollector(level: 4)]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/TraitDeclarationCollector.cpp')]
final class TraitDeclarationCollector implements Collector
{

	public function getNodeType(): string
	{
		return Node\Stmt\Trait_::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		if ($node->namespacedName === null) {
			return null;
		}

		return [$node->namespacedName->toString(), $node->getStartLine()];
	}

}
