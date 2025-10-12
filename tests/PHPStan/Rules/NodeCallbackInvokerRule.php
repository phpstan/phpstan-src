<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;

/**
 * @implements Rule<Node\Stmt\Echo_>
 */
class NodeCallbackInvokerRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Echo_::class;
	}

	public function processNode(Node $node, NodeCallbackInvoker&Scope $scope): array
	{
		if ((bool) $node->getAttribute('virtual', false)) {
			// prevent infinite recursion
			return [
				RuleErrorBuilder::message('found virtual echo')
					->identifier('tests.nodeCallbackInvoker')
					->build(),
			];
		}

		$scope->invokeNodeCallback(new Node\Stmt\Echo_(
			[new Node\Scalar\String_('virtual')],
			['startLine' => $node->getStartLine() + 1, 'virtual' => true],
		));

		return [
			RuleErrorBuilder::message('found echo')
				->identifier('tests.nodeCallbackInvoker')
				->build(),
		];
	}

}
