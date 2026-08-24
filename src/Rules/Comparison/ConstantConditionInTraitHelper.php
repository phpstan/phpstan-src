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
		$this->emitNoErrorForKey($ruleName, $scope, $this->exprString($expr));
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
		$this->emitErrorForKey($ruleName, $scope, $expr, $this->exprString($expr), $value, $ruleError);
	}

	/**
	 * Like emitNoError(), but for callers that cannot key their check by a single Expr
	 * (e.g. one Rule node covering several distinct checks at the same location).
	 *
	 * @param class-string<Rule<covariant Node>> $ruleName
	 */
	public function emitNoErrorForKey(
		string $ruleName,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
		string $key,
	): void
	{
		if (!$scope->isInTrait()) {
			return;
		}

		$scope->emitCollectedData(ConstantConditionInTraitCollector::class, [
			$ruleName,
			$scope->getTraitReflection()->getName(),
			$key,
			null,
		]);
	}

	/**
	 * Like emitError(), but for callers that cannot key their check by a single Expr
	 * (e.g. one Rule node covering several distinct checks at the same location).
	 *
	 * @param class-string<Rule<covariant Node>> $ruleName
	 */
	public function emitErrorForKey(
		string $ruleName,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
		Node $node,
		string $key,
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

		$scope->emitCollectedData(ConstantConditionInTraitCollector::class, [
			$ruleName,
			$scope->getTraitReflection()->getName(),
			$key,
			$value,
			$this->ruleErrorTransformer->transform($ruleError, $scope, [], $node),
		]);
	}

}
