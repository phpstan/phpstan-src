<?php

namespace CollectedData;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\CollectedDataNode;
use function PHPStan\Testing\assertType;

/**
 * @implements Collector<Node\Expr\MethodCall, int>
 */
class TestCollector implements Collector
{
	public function getNodeType(): string
	{
		return Node\Expr\MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		return 1;
	}

}

class Foo
{

	public function doFoo(CollectedDataNode $node): void
	{
		assertType('array<string, list<int>>', $node->get(TestCollector::class));
	}

}
