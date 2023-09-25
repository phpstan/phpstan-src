<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CatchWithUnthrownExceptionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\NeverType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<CatchWithUnthrownExceptionNode>
 */
class CatchWithUnthrownExceptionRule implements Rule
{

	public function __construct(
		private ExceptionTypeResolver $exceptionTypeResolver,
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
		if ($node->getCaughtType() instanceof NeverType) {
			return [
				RuleErrorBuilder::message(
					sprintf('Dead catch - %s is already caught above.', $node->getOriginalCaughtType()->describe(VerbosityLevel::typeOnly())),
				)->line($node->getLine())->build(),
			];
		}

		$isCheckedException = TrinaryLogic::createFromBoolean($this->reportUncheckedExceptionDeadCatch)->lazyOr(
			$node->getCaughtType()->getObjectClassNames(),
			fn (string $objectClassName) => TrinaryLogic::createFromBoolean($this->exceptionTypeResolver->isCheckedException($objectClassName, $scope)),
		);
		if ($isCheckedException->no()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(
				sprintf('Dead catch - %s is never thrown in the try block.', $node->getCaughtType()->describe(VerbosityLevel::typeOnly())),
			)->line($node->getLine())->build(),
		];
	}

}
