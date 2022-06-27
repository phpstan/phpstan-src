<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use function array_map;

/**
 * @implements Collector<Node\Stmt\TraitUse, list<string>>
 */
class TraitUseCollector implements Collector
{

	public function getNodeType(): string
	{
		return Node\Stmt\TraitUse::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		return array_map(static fn (Node\Name $traitName) => $traitName->toString(), $node->traits);
	}

}
