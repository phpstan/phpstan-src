<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AutowiredServices;

use PhpParser\Node;
use PhpParser\Node\Stmt\Echo_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Echo_>
 */
#[RegisteredRule(level: 9)]
final class TestedDiscoveredHighLevelRule implements Rule
{

	public function getNodeType(): string
	{
		return Echo_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [];
	}

}
