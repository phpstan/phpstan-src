<?php declare(strict_types = 1);

namespace ResultCacheE2ESymbolLookupsRules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<FuncCall, int>
 */
final class LegacyCallCollector implements Collector
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): ?int
	{
		if (!$node->name instanceof Name || $node->name->toLowerString() !== 'collectedlegacycall') {
			return null;
		}

		return $node->getStartLine();
	}

}
