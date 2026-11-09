<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\CatchTypeNode;
use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<CatchTypeNode>
 */
#[RegisteredRule(level: 4)]
final class CatchWithUnthrownExceptionRule implements Rule
{

	public function __construct(
		#[AutowiredParameter(ref: '@exceptionTypeResolver')]
		private ExceptionTypeResolver $exceptionTypeResolver,
		private ConstantConditionInTraitHelper $constantConditionInTraitHelper,
		#[AutowiredParameter(ref: '%exceptions.reportUncheckedExceptionDeadCatch%')]
		private bool $reportUncheckedExceptionDeadCatch,
	)
	{
	}

	public function getNodeType(): string
	{
		return CatchTypeNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		if ($node->isThrown()) {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node->getTypeNode());
			return [];
		}

		if ($node->getCaughtType() instanceof NeverType) {
			$ruleError = RuleErrorBuilder::message(
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
					$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node->getTypeNode());
					return [];
				}
			}

			$ruleError = RuleErrorBuilder::message(
				sprintf('Dead catch - %s is never thrown in the try block.', $node->getCaughtType()->describe(VerbosityLevel::typeOnly())),
			)
				->line($node->getStartLine())
				->identifier('catch.neverThrown')
				->build();
		}

		if ($scope->isInTrait()) {
			// The message already distinguishes the possible outcomes,
			// so the contexts only need to be told apart by error/no error.
			$this->constantConditionInTraitHelper->emitError(self::class, $scope, $node->getTypeNode(), true, $ruleError);
			return [];
		}

		return [$ruleError];
	}

}
