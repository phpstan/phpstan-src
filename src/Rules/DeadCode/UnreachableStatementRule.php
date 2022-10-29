<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\UnreachableStatementNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<UnreachableStatementNode>
 */
class UnreachableStatementRule implements Rule
{

	public function getNodeType(): string
	{
		return UnreachableStatementNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$originalStatement = $node->getOriginalStatement();
		if ($originalStatement instanceof Node\Stmt\Nop || $originalStatement instanceof Node\Stmt\InlineHTML) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Unreachable statement - code above always terminates.')
				->identifier('deadCode.unreachableStatement')
				->metadata([
					'depth' => $node->getAttribute('statementDepth'),
					'order' => $node->getAttribute('statementOrder'),
				])
				->build(),
		];
	}

}
