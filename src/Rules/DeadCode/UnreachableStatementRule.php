<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\UnreachableStatementNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\UnreachableStatementNode>
 */
class UnreachableStatementRule implements Rule
{

	public function getNodeType(): string
	{
		return UnreachableStatementNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->getOriginalStatement() instanceof Node\Stmt\Nop) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Unreachable statement - code above always terminates.')->build(),
		];
	}

}
