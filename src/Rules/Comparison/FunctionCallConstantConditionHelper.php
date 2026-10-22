<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
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
final class FunctionCallConstantConditionHelper
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
	 * Whether the condition is a function/method/static call that the
	 * ImpossibleCheckType* rules might own. For these the constant-condition
	 * reporting is deferred to FunctionCallConstantConditionRule, which
	 * deduplicates against ImpossibleCheckTypeReportedCollector markers.
	 */
	public function isTypeCheckCandidate(Expr $expr): bool
	{
		return (
			$expr instanceof FuncCall
			|| $expr instanceof MethodCall
			|| $expr instanceof StaticCall
		) && !$expr->isFirstClassCallable();
	}

	/**
	 * @param class-string<Rule<covariant Node>> $ruleName
	 */
	public function emitFunctionCallNoError(
		string $ruleName,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
		Expr $expr,
	): void
	{
		$scope->emitCollectedData(FunctionCallConstantConditionCollector::class, [
			$ruleName,
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$this->exprString($expr),
			null,
		]);
	}

	/**
	 * @param class-string<Rule<covariant Node>> $ruleName
	 */
	public function emitFunctionCallError(
		string $ruleName,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
		Expr $expr,
		bool $value,
		RuleError $ruleError,
	): void
	{
		if ($ruleError instanceof FixableNodeRuleError) {
			throw new ShouldNotHappenException('Fixable errors are not supported by FunctionCallConstantConditionHelper.');
		}

		$scope->emitCollectedData(FunctionCallConstantConditionCollector::class, [
			$ruleName,
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$this->exprString($expr),
			$value,
			$this->ruleErrorTransformer->transform($ruleError, $scope, [], $expr),
		]);
	}

	public function emitImpossibleCheckReported(
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
		Expr $expr,
	): void
	{
		$scope->emitCollectedData(ImpossibleCheckTypeReportedCollector::class, [
			$this->exprString($expr),
		]);
	}

}
