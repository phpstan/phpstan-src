<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Empty_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<Empty_>
 */
#[AutowiredService]
final class EmptyHandler implements ExprHandler
{

	public function __construct(
		private NonNullabilityHelper $nonNullabilityHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Empty_;
	}

	/**
	 * @param Empty_ $expr
	 */
	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$result = $scope->issetCheck($expr->expr, static function (Type $type): ?bool {
			$isNull = $type->isNull();
			$isFalsey = $type->toBoolean()->isFalse();
			if ($isNull->maybe()) {
				return null;
			}
			if ($isFalsey->maybe()) {
				return null;
			}

			if ($isNull->yes()) {
				return $isFalsey->no();
			}

			return !$isFalsey->yes();
		});
		if ($result === null) {
			return new BooleanType();
		}

		return new ConstantBooleanType(!$result);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$nonNullabilityResult = $this->nonNullabilityHelper->ensureNonNullability($scope, $expr->expr);
		$scope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $expr->expr);
		$result = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $result->getScope();
		$scope = $this->nonNullabilityHelper->revertNonNullability($scope, $nonNullabilityResult->getSpecifiedExpressions());
		$scope = $nodeScopeResolver->lookForUnsetAllowedUndefinedExpressions($scope, $expr->expr);

		return new ExpressionResult(
			$scope,
			hasYield: $result->hasYield(),
			isAlwaysTerminating: $result->isAlwaysTerminating(),
			throwPoints: $result->getThrowPoints(),
			impurePoints: $result->getImpurePoints(),
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
