<?php declare(strict_types = 1);

namespace Test\RuleLib;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Stmt\Class_>
 */
class MyRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [RuleErrorBuilder::message('the rule says one thing')->identifier('test.myRule')->build()];
	}

}
