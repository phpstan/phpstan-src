<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\RuleErrorTransformer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\ShouldNotHappenException;
use function sprintf;

#[AutowiredService]
final class ConstantConditionInTraitHelper
{

	public function __construct(
		private ExprPrinter $exprPrinter,
		private RuleErrorTransformer $ruleErrorTransformer,
	)
	{
	}

	private function exprString(Expr $expr): string
	{
		return sprintf('%s:%d', $this->exprPrinter->printExpr($expr), $expr->getStartLine());
	}

	/**
	 * @param class-string<Rule<covariant Node>> $ruleName
	 */
	public function emitNoError(
		string $ruleName,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
		Expr $expr,
	): void
	{
		if (!$scope->isInTrait()) {
			return;
		}

		$scope->emitCollectedData(ConstantConditionInTraitCollector::class, [
			$ruleName,
			$scope->getTraitReflection()->getName(),
			$this->exprString($expr),
			null,
		]);
	}

	/**
	 * $value is what the using classes have to agree on for the error to be reported. A condition's
	 * value is a bool; a rule whose verdict has more than two outcomes passes the outcome itself, so
	 * that two using classes reaching the same expression for different reasons still counts as
	 * disagreement.
	 *
	 * @param class-string<Rule<covariant Node>> $ruleName
	 */
	public function emitError(
		string $ruleName,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
		Expr $expr,
		bool|string $value,
		RuleError $ruleError,
	): void
	{
		if ($ruleError instanceof FixableNodeRuleError) {
			throw new ShouldNotHappenException('Fixable errors are not supported by ConstantConditionInTraitHelper.');
		}

		if (!$scope->isInTrait()) {
			return;
		}

		$scope->emitCollectedData(ConstantConditionInTraitCollector::class, [
			$ruleName,
			$scope->getTraitReflection()->getName(),
			$this->exprString($expr),
			$value,
			$this->ruleErrorTransformer->transform($ruleError, $scope, [], $expr),
		]);
	}

}
