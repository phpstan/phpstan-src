<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * Registered regardless of the level, also with customRulesetUsed: which analysed files declare
 * a trait decides which unmatched ignoreErrors entries are reported in a partial analysis.
 *
 * @implements Collector<Node\Stmt\Trait_, array{string, int}>
 */
#[AutowiredService]
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
