<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\CatchWithUnthrownExceptionNode;
use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;
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
		private ConstantConditionInTraitHelper $constantConditionInTraitHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return CatchWithUnthrownExceptionNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		if ($node->getCaughtType() instanceof NeverType) {
			$error = RuleErrorBuilder::message(
				sprintf('Dead catch - %s is already caught above.', $node->getOriginalCaughtType()->describe(VerbosityLevel::typeOnly())),
			)
				->line($node->getStartLine())
				->identifier('catch.alreadyCaught')
				->build();
		} else {
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

			$error = RuleErrorBuilder::message(
				sprintf('Dead catch - %s is never thrown in the try block.', $node->getCaughtType()->describe(VerbosityLevel::typeOnly())),
			)
				->line($node->getStartLine())
				->identifier('catch.neverThrown')
				->build();
		}

		if ($scope->isInTrait()) {
			// A trait's catch can be dead in the context of one class using the trait and
			// alive in the context of another, e.g. when it depends on whether an abstract
			// method gets overridden. Let the collector compare the verdicts of all the
			// classes using the trait instead of reporting right away; the alive ones are
			// recorded by CatchWithThrownExceptionInTraitRule under the same key.
			$this->constantConditionInTraitHelper->emitErrorForKey(
				self::class,
				$scope,
				$node->getOriginalNode(),
				DeadCatchInTraitKey::create($node->getOriginalNode(), $node->getOriginalCaughtType()),
				true,
				$error,
			);
			return [];
		}

		return [$error];
	}

}
