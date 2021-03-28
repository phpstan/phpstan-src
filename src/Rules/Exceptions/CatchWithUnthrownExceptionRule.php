<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CatchWithUnthrownExceptionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<CatchWithUnthrownExceptionNode>
 */
class CatchWithUnthrownExceptionRule implements Rule
{

	public function getNodeType(): string
	{
		return CatchWithUnthrownExceptionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [
			RuleErrorBuilder::message(
				sprintf('Dead catch - %s is never thrown in the try block.', $node->getCaughtType()->describe(VerbosityLevel::typeOnly()))
			)->line($node->getLine())->build(),
		];
	}

}
