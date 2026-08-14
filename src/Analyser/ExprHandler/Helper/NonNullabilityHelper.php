<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\EnsuredNonNullabilityResult;
use PHPStan\Analyser\EnsuredNonNullabilityResultExpression;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\PerFileAnalysisResettable;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_pop;
use function count;

#[AutowiredService]
final class NonNullabilityHelper implements PerFileAnalysisResettable
{

	/**
	 * The ensures currently in effect during the walk, innermost last. An
	 * ensure writes non-null "device" types into the scope so nested fetches
	 * walk without spurious possibly-null noise - indistinguishable from
	 * genuine narrowing in scope state. Handlers whose semantics depend on an
	 * expression's REAL nullability (a nullsafe operator's short-circuit)
	 * consult this stack for the pre-device type instead.
	 *
	 * @var list<array<string, array{Type, Type}>>
	 */
	private array $activeEnsures = [];

	public function __construct(private ExprPrinter $exprPrinter)
	{
	}

	/**
	 * An internal error escaping between an ensure and its revert would leave
	 * stale frames matched by common print keys ($this->foo) for the rest of
	 * the worker's batch - the per-file reset clears them.
	 */
	public function resetFileAnalysisState(): void
	{
		$this->activeEnsures = [];
	}

	/**
	 * The pre-device type an active ensure saved for this expression, or null
	 * when no ensure covers it.
	 */
	public function getActiveEnsuredOriginalType(Expr $expr, bool $native): ?Type
	{
		if ($this->activeEnsures === []) {
			return null;
		}

		$key = $this->exprPrinter->printExpr($expr);
		for ($i = count($this->activeEnsures) - 1; $i >= 0; $i--) {
			if (isset($this->activeEnsures[$i][$key])) {
				return $this->activeEnsures[$i][$key][$native ? 1 : 0];
			}
		}

		return null;
	}

	public function ensureShallowNonNullability(MutatingScope $scope, MutatingScope $originalScope, Expr $exprToSpecify): EnsuredNonNullabilityResult
	{
		$result = $this->doEnsureShallowNonNullability($scope, $originalScope, $exprToSpecify);
		$this->pushActiveEnsure($result);

		return $result;
	}

	private function pushActiveEnsure(EnsuredNonNullabilityResult $result): void
	{
		$originals = [];
		foreach ($result->getSpecifiedExpressions() as $specifiedExpression) {
			$originals[$this->exprPrinter->printExpr($specifiedExpression->getExpression())] = [
				$specifiedExpression->getOriginalType(),
				$specifiedExpression->getOriginalNativeType(),
			];
		}
		$this->activeEnsures[] = $originals;
	}

	private function doEnsureShallowNonNullability(MutatingScope $scope, MutatingScope $originalScope, Expr $exprToSpecify): EnsuredNonNullabilityResult
	{
		// the expression has not been processed into the storage yet (this runs
		// before processExprNode) - derive its current type from the scope's
		// tracked state instead of pricing the node on demand.
		$exprType = $scope->getStateType($exprToSpecify);
		$isNull = $exprType->isNull();
		if ($isNull->yes()) {
			return new EnsuredNonNullabilityResult($scope, []);
		}

		$hasExpressionType = $originalScope->hasExpressionType($exprToSpecify);

		$exprTypeWithoutNull = TypeCombinator::removeNull($exprType);
		if ($exprType->equals($exprTypeWithoutNull)) {
			$originalExprType = $originalScope->getStateType($exprToSpecify);
			if (!$originalExprType->equals($exprTypeWithoutNull)) {
				$originalNativeType = $originalScope->doNotTreatPhpDocTypesAsCertain()->getStateType($exprToSpecify);

				return new EnsuredNonNullabilityResult($scope, [
					new EnsuredNonNullabilityResultExpression($exprToSpecify, $originalExprType, $originalNativeType, $hasExpressionType),
				]);
			}
			return new EnsuredNonNullabilityResult($scope, []);
		}

		$specifiedExpressions = [];

		// When narrowing an ArrayDimFetch, specifyExpressionType also recursively
		// narrows the parent array's offset type via intersection with HasOffsetValueType.
		// To properly revert this, we must also save and restore the parent expression's type.
		if ($exprToSpecify instanceof Expr\ArrayDimFetch && $exprToSpecify->dim !== null) {
			$parentExpr = $exprToSpecify->var;
			$specifiedExpressions[] = new EnsuredNonNullabilityResultExpression(
				$parentExpr,
				$scope->getStateType($parentExpr),
				$scope->doNotTreatPhpDocTypesAsCertain()->getStateType($parentExpr),
				$originalScope->hasExpressionType($parentExpr),
			);
		}

		// Keep the "might not be defined" certainty of variables so that rules
		// reporting possibly undefined variables still see it. For any other
		// expression a Maybe certainty would make the narrowed type invisible to
		// Scope::getType(), throwing the narrowing away.
		$certainty = TrinaryLogic::createYes();
		if ($hasExpressionType->maybe() && $exprToSpecify instanceof Variable) {
			$certainty = $hasExpressionType;
		}

		$nativeType = $scope->doNotTreatPhpDocTypesAsCertain()->getStateType($exprToSpecify);
		$specifiedExpressions[] = new EnsuredNonNullabilityResultExpression($exprToSpecify, $exprType, $nativeType, $certainty);
		$scope = $scope->specifyExpressionType(
			$exprToSpecify,
			$exprTypeWithoutNull,
			TypeCombinator::removeNull($nativeType),
			$certainty,
		);

		return new EnsuredNonNullabilityResult(
			$scope,
			$specifiedExpressions,
		);
	}

	public function ensureNonNullability(MutatingScope $scope, Expr $expr): EnsuredNonNullabilityResult
	{
		$specifiedExpressions = [];
		$originalScope = $scope;
		$scope = $this->lookForExpressionCallback($scope, $expr, function ($scope, $expr) use (&$specifiedExpressions, $originalScope) {
			$result = $this->doEnsureShallowNonNullability($scope, $originalScope, $expr);
			foreach ($result->getSpecifiedExpressions() as $specifiedExpression) {
				$specifiedExpressions[] = $specifiedExpression;
			}
			return $result->getScope();
		}, false);

		$result = new EnsuredNonNullabilityResult($scope, $specifiedExpressions);
		$this->pushActiveEnsure($result);

		return $result;
	}

	/**
	 * @param EnsuredNonNullabilityResultExpression[] $specifiedExpressions
	 */
	public function revertNonNullability(MutatingScope $scope, array $specifiedExpressions): MutatingScope
	{
		array_pop($this->activeEnsures);
		foreach ($specifiedExpressions as $specifiedExpressionResult) {
			if ($specifiedExpressionResult->getCertainty()->no()) {
				$scope = $scope->invalidateExpression($specifiedExpressionResult->getExpression());
				continue;
			}
			$scope = $scope->specifyExpressionType(
				$specifiedExpressionResult->getExpression(),
				$specifiedExpressionResult->getOriginalType(),
				$specifiedExpressionResult->getOriginalNativeType(),
				$specifiedExpressionResult->getCertainty(),
			);
		}

		return $scope;
	}

	/**
	 * @param Closure(MutatingScope, Expr): MutatingScope $callback
	 */
	private function lookForExpressionCallback(MutatingScope $scope, Expr $expr, Closure $callback, bool $includeExpr = true): MutatingScope
	{
		// $includeExpr is false only for the outermost operand: ensuring its chain
		// links non-null lets it be walked without spurious "possibly null" noise,
		// but the operand's own value must keep its real (nullable) type - that is
		// the type the isset/empty/?? verdict and narrowing read from its result.
		if ($includeExpr && (!$expr instanceof ArrayDimFetch || $expr->dim !== null)) {
			$scope = $callback($scope, $expr);
		}

		if ($expr instanceof ArrayDimFetch) {
			$scope = $this->lookForExpressionCallback($scope, $expr->var, $callback);
		} elseif ($expr instanceof PropertyFetch || $expr instanceof Expr\NullsafePropertyFetch) {
			$scope = $this->lookForExpressionCallback($scope, $expr->var, $callback);
		} elseif ($expr instanceof StaticPropertyFetch && $expr->class instanceof Expr) {
			$scope = $this->lookForExpressionCallback($scope, $expr->class, $callback);
		} elseif ($expr instanceof List_) {
			foreach ($expr->items as $item) {
				if ($item === null) {
					continue;
				}

				$scope = $this->lookForExpressionCallback($scope, $item->value, $callback);
			}
		}

		return $scope;
	}

}
