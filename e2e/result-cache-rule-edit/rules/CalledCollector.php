<?php declare(strict_types = 1);

namespace ResultCacheE2ERuleEdit;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<FuncCall, string>
 */
final class CalledCollector implements Collector
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		return $node->name instanceof Name ? $node->name->toString() : null;
	}

}
