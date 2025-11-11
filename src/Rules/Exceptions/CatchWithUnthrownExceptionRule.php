<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\CatchWithUnthrownExceptionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<CatchWithUnthrownExceptionNode>
 */
#[RegisteredRule(level: 4)]
final class CatchWithUnthrownExceptionRule implements Rule
{

	public function __construct(
		#[AutowiredParameter(ref: '@exceptionTypeResolver')]
		private ExceptionTypeResolver $exceptionTypeResolver,
		#[AutowiredParameter(ref: '%exceptions.reportUncheckedExceptionDeadCatch%')]
		private bool $reportUncheckedExceptionDeadCatch,
	)
	{
	}

	public function getNodeType(): string
	{
		return CatchWithUnthrownExceptionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->getCaughtType()->isNever()->yes()) {
			return [
				RuleErrorBuilder::message(
					sprintf('Dead catch - %s is already caught above.', $node->getOriginalCaughtType()->describe(VerbosityLevel::typeOnly())),
				)
					->line($node->getStartLine())
					->identifier('catch.alreadyCaught')
					->build(),
			];
		}

		if (!$this->reportUncheckedExceptionDeadCatch) {
			$isCheckedException = false;
			foreach ($node->getCaughtType()->getObjectClassNames() as $objectClassName) {
				if ($this->exceptionTypeResolver->isCheckedException($objectClassName, $scope)) {
					$isCheckedException = true;
					break;
				}
			}

			if (!$isCheckedException) {
				return [];
			}
		}

		return [
			RuleErrorBuilder::message(
				sprintf('Dead catch - %s is never thrown in the try block.', $node->getCaughtType()->describe(VerbosityLevel::typeOnly())),
			)
				->line($node->getStartLine())
				->identifier('catch.neverThrown')
				->build(),
		];
	}

}
