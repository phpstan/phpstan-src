<?php

namespace NodeConnectingVisitorRule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class MyRule implements Rule
{

	/** @var 'parent'|'myCustomAttribute' */
	public string $attrName;

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$parent = $node->getAttribute("parent");
		$custom = $node->getAttribute("myCustomAttribute");
		$parent = $node->getAttribute($this->attrName);

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
