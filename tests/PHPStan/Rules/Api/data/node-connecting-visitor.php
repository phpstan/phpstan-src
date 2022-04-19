<?php

namespace NodeConnectingVisitorRule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class MyRule implements Rule
{
	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$parent = $node->getAttribute("parent");
		$custom = $node->getAttribute("myCustomAttribute");

		return [];
	}

}

class Foo
{

	public function doFoo(Node $node): void
	{
		$parent = $node->getAttribute("parent");
	}

}
