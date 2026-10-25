<?php declare(strict_types = 1);

namespace ResultCacheCollectedDataRulesTracking;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<FuncCall, array{string, int}>
 */
final class ViewCollector implements Collector
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		if (!$node->name instanceof Name || $node->name->toLowerString() !== 'view') {
			return null;
		}

		$args = $node->getArgs();
		if (!isset($args[0]) || !$args[0]->value instanceof String_) {
			return null;
		}

		return [$args[0]->value->value, $node->getStartLine()];
	}

}
