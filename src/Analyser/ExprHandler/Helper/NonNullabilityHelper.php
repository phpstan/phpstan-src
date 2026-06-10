<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\EnsuredNonNullabilityResult;
use PHPStan\Analyser\EnsuredNonNullabilityResultExpression;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

#[AutowiredService]
final class NonNullabilityHelper
{

	/**
	 * @param (callable(MutatingScope): MutatingScope)|null $askScopeFactory wraps
	 *        the scope used for type asks (an adapter in the new world); the
	 *        specification itself happens on the unwrapped scopes. Null keeps
	 *        the guarded direct asks (PHPSTAN_FNSR=0).
	 */
	public function ensureShallowNonNullability(MutatingScope $scope, Scope $originalScope, Expr $exprToSpecify, ?callable $askScopeFactory = null): EnsuredNonNullabilityResult
	{
		$askScope = $askScopeFactory !== null ? $askScopeFactory($scope) : $scope;
		$exprType = $askScope->getType($exprToSpecify);
		$isNull = $exprType->isNull();
		if ($isNull->yes()) {
			return new EnsuredNonNullabilityResult($scope, []);
		}

		$hasExpressionType = $originalScope->hasExpressionType($exprToSpecify);

		$exprTypeWithoutNull = TypeCombinator::removeNull($exprType);
		if ($exprType->equals($exprTypeWithoutNull)) {
			if (!$originalScope instanceof MutatingScope) {
				throw new ShouldNotHappenException();
			}
			$originalAskScope = $askScopeFactory !== null ? $askScopeFactory($originalScope) : $originalScope;
			$originalExprType = $originalAskScope->getType($exprToSpecify);
			if (!$originalExprType->equals($exprTypeWithoutNull)) {
				$originalNativeType = $originalAskScope->getNativeType($exprToSpecify);

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
				$askScope->getType($parentExpr),
				$askScope->getNativeType($parentExpr),
				$originalScope->hasExpressionType($parentExpr),
			);
		}

		// keep certainty
		$certainty = TrinaryLogic::createYes();
		if (!$hasExpressionType->no()) {
			$certainty = $hasExpressionType;
		}

		$nativeType = $askScope->getNativeType($exprToSpecify);
		$specifiedExpressions[] = new EnsuredNonNullabilityResultExpression($exprToSpecify, $exprType, $nativeType, $certainty);
		$scope = $scope->specifyExpressionType(
			$exprToSpecify,
			$exprTypeWithoutNull,
			TypeCombinator::removeNull($nativeType),
			TrinaryLogic::createYes(),
		);

		return new EnsuredNonNullabilityResult(
			$scope,
			$specifiedExpressions,
		);
	}

	/**
	 * New-world variant of ensureShallowNonNullability(): the expression's type
	 * comes from its already-known ExpressionResult instead of Scope::getType().
	 * The ArrayDimFetch parent record still reads the parent's type through the
	 * guarded legacy bridge (PHPSTAN_FNSR=0) until ArrayDimFetchHandler migrates.
	 */
	public function ensureShallowNonNullabilityFromTypes(MutatingScope $scope, Expr $exprToSpecify, Type $exprType, Type $nativeType): EnsuredNonNullabilityResult
	{
		$isNull = $exprType->isNull();
		if ($isNull->yes()) {
			return new EnsuredNonNullabilityResult($scope, []);
		}

		$exprTypeWithoutNull = TypeCombinator::removeNull($exprType);
		if ($exprType->equals($exprTypeWithoutNull)) {
			return new EnsuredNonNullabilityResult($scope, []);
		}

		$specifiedExpressions = [];
		if ($exprToSpecify instanceof Expr\ArrayDimFetch && $exprToSpecify->dim !== null) {
			$parentExpr = $exprToSpecify->var;
			$specifiedExpressions[] = new EnsuredNonNullabilityResultExpression(
				$parentExpr,
				$scope->getType($parentExpr),
				$scope->getNativeType($parentExpr),
				$scope->hasExpressionType($parentExpr),
			);
		}

		$hasExpressionType = $scope->hasExpressionType($exprToSpecify);
		$certainty = TrinaryLogic::createYes();
		if (!$hasExpressionType->no()) {
			$certainty = $hasExpressionType;
		}

		$specifiedExpressions[] = new EnsuredNonNullabilityResultExpression($exprToSpecify, $exprType, $nativeType, $certainty);
		$scope = $scope->specifyExpressionType(
			$exprToSpecify,
			$exprTypeWithoutNull,
			TypeCombinator::removeNull($nativeType),
			TrinaryLogic::createYes(),
		);

		return new EnsuredNonNullabilityResult(
			$scope,
			$specifiedExpressions,
		);
	}

	/**
	 * @param (callable(MutatingScope): MutatingScope)|null $askScopeFactory
	 */
	public function ensureNonNullability(MutatingScope $scope, Expr $expr, ?callable $askScopeFactory = null): EnsuredNonNullabilityResult
	{
		$specifiedExpressions = [];
		$originalScope = $scope;
		$scope = $this->lookForExpressionCallback($scope, $expr, function ($scope, $expr) use (&$specifiedExpressions, $originalScope, $askScopeFactory) {
			$result = $this->ensureShallowNonNullability($scope, $originalScope, $expr, $askScopeFactory);
			foreach ($result->getSpecifiedExpressions() as $specifiedExpression) {
				$specifiedExpressions[] = $specifiedExpression;
			}
			return $result->getScope();
		});

		return new EnsuredNonNullabilityResult($scope, $specifiedExpressions);
	}

	/**
	 * @param EnsuredNonNullabilityResultExpression[] $specifiedExpressions
	 */
	public function revertNonNullability(MutatingScope $scope, array $specifiedExpressions): MutatingScope
	{
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
	private function lookForExpressionCallback(MutatingScope $scope, Expr $expr, Closure $callback): MutatingScope
	{
		if (!$expr instanceof ArrayDimFetch || $expr->dim !== null) {
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
