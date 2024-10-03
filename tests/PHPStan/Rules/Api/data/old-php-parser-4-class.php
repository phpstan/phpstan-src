<?php

namespace OldPhpParser4Class;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class Foo
{

	public function doFoo(): void
	{
		echo \PhpParser\Node\Expr\ArrayItem::class;
	}

}

class FooRule implements Rule
{

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr\ArrayItem::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [];
	}

}
