<?php declare(strict_types = 1);

namespace Test\RuleExt;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Stmt\Class_>
 */
final class ClassNameRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [
			RuleErrorBuilder::message('extension rule version 1')
				->identifier('test.ruleExt')
				->build(),
		];
	}

}
