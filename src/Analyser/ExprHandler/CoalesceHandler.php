<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\NeverType;
use function array_merge;

/**
 * @implements ExprHandler<Coalesce>
 */
#[AutowiredService]
final class CoalesceHandler implements ExprHandler
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private NonNullabilityHelper $nonNullabilityHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Coalesce;
	}

	public function processExpr(Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$nonNullabilityResult = $this->nonNullabilityHelper->ensureNonNullability($scope, $expr->left);
		$condScope = $this->nodeScopeResolver->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $expr->left);
		$condResult = $this->nodeScopeResolver->processExprNode($stmt, $expr->left, $condScope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $this->nonNullabilityHelper->revertNonNullability($condResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());
		$scope = $this->nodeScopeResolver->lookForUnsetAllowedUndefinedExpressions($scope, $expr->left);

		$rightScope = $scope->filterByFalseyValue($expr);
		$rightResult = $this->nodeScopeResolver->processExprNode($stmt, $expr->right, $rightScope, $storage, $nodeCallback, $context->enterDeep());
		$rightExprType = $scope->getType($expr->right);
		if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
			$scope = $scope->filterByTruthyValue(new Expr\Isset_([$expr->left]));
		} else {
			$scope = $scope->filterByTruthyValue(new Expr\Isset_([$expr->left]))->mergeWith($rightResult->getScope());
		}

		return new ExpressionResult(
			$scope,
			hasYield: $condResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $condResult->isAlwaysTerminating(),
			throwPoints: array_merge($condResult->getThrowPoints(), $rightResult->getThrowPoints()),
			impurePoints: array_merge($condResult->getImpurePoints(), $rightResult->getImpurePoints()),
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
