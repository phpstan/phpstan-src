<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @implements Collector<Node\Expr\FuncCall, array{}>
 */
class DummyCollector implements Collector
{

	public function getNodeType(): string
	{
		return 'PhpParser\Node\Expr\FuncCall';
	}

	public function processNode(Node $node, Scope $scope)
	{
		return [];
	}

}
