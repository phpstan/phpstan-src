<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CatchWithUnthrownExceptionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

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
		if ($node->getCaughtType() instanceof NeverType) {
			return [
				RuleErrorBuilder::message(
					sprintf('Dead catch - %s is already caught above.', $node->getOriginalCaughtType()->describe(VerbosityLevel::typeOnly())),
				)
					->line($node->getLine())
					->identifier('catch.alreadyCaught')
					->build(),
			];
		}

		return [
			RuleErrorBuilder::message(
				sprintf('Dead catch - %s is never thrown in the try block.', $node->getCaughtType()->describe(VerbosityLevel::typeOnly())),
			)
				->line($node->getLine())
				->identifier('catch.neverThrown')
				->build(),
		];
	}

}
