<?php declare(strict_types = 1);

namespace PHPStan\Rules\ExprUsedAsString;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ExprUsedAsStringNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function get_class;

/**
 * @implements Rule<ExprUsedAsStringNode>
 */
class ExprUsedAsStringTestRule implements Rule
{

	public function getNodeType(): string
	{
		return ExprUsedAsStringNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$expr = $node->getExpression();
		$originalNode = $node->getOriginalNode();

		return [
			RuleErrorBuilder::message('Expression used as string: ' . get_class($expr) . ' in ' . get_class($originalNode))
				->identifier('tests.exprUsedAsString')
				->build(),
		];
	}

}
