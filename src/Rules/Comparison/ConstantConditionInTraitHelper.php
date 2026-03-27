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

		$exprString = sprintf('%s:%d', $this->exprPrinter->printExpr($expr), $expr->getStartLine());
		$scope->emitCollectedData(ConstantConditionInTraitCollector::class, [
			$ruleName,
			$scope->getTraitReflection()->getName(),
			$exprString,
			null,
		]);
	}

	/**
	 * @param class-string<Rule<covariant Node>> $ruleName
	 */
	public function emitError(
		string $ruleName,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
		Expr $expr,
		bool $value,
		RuleError $ruleError,
	): void
	{
		if ($ruleError instanceof FixableNodeRuleError) {
			throw new ShouldNotHappenException('Fixable errors are not supported by ConstantConditionInTraitHelper.');
		}

		if (!$scope->isInTrait()) {
			return;
		}

		$exprString = sprintf('%s:%d', $this->exprPrinter->printExpr($expr), $expr->getStartLine());
		$scope->emitCollectedData(ConstantConditionInTraitCollector::class, [
			$ruleName,
			$scope->getTraitReflection()->getName(),
			$exprString,
			$value,
			$this->ruleErrorTransformer->transform($ruleError, $scope, [], $expr),
		]);
	}

}
