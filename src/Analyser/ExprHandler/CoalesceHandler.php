<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;

/**
 * @implements ExprHandler<Coalesce>
 */
#[AutowiredService]
final class CoalesceHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private NonNullabilityHelper $nonNullabilityHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Coalesce;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$issetLeftExpr = new Expr\Isset_([$expr->left]);

		$result = $scope->issetCheck($expr->left, static function (Type $type): ?bool {
			$isNull = $type->isNull();
			if ($isNull->maybe()) {
				return null;
			}

			return !$isNull->yes();
		});

		if ($result !== null && $result !== false) {
			return TypeCombinator::removeNull($scope->filterByTruthyValue($issetLeftExpr)->getType($expr->left));
		}

		$rightType = $scope->filterByFalseyValue($issetLeftExpr)->getType($expr->right);

		if ($result === null) {
			return TypeCombinator::union(
				TypeCombinator::removeNull($scope->filterByTruthyValue($issetLeftExpr)->getType($expr->left)),
				$rightType,
			);
		}

		return $rightType;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$nonNullabilityResult = $this->nonNullabilityHelper->ensureNonNullability($scope, $expr->left);
		$condScope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $expr->left);
		$condResult = $nodeScopeResolver->processExprNode($stmt, $expr->left, $condScope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $this->nonNullabilityHelper->revertNonNullability($condResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());
		$scope = $nodeScopeResolver->lookForUnsetAllowedUndefinedExpressions($scope, $expr->left);

		$rightScope = $scope->filterByFalseyValue($expr);
		$rightResult = $nodeScopeResolver->processExprNode($stmt, $expr->right, $rightScope, $storage, $nodeCallback, $context->enterDeep());
		$rightExprType = $rightResult->getType();
		if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
			$scope = $scope->filterByTruthyValue(new Expr\Isset_([$expr->left]));
		} else {
			$scope = $scope->filterByTruthyValue(new Expr\Isset_([$expr->left]))->mergeWith($rightResult->getScope());
		}

		return $this->expressionResultFactory->create(
			$expr,
			$scope,
			typeCallback: static function (Expr $uninteresting, MutatingScope $scope) use ($condResult, $rightResult): Type {
				$leftType = $condResult->getTypeForScope($scope);
				$rightType = $rightResult->getTypeForScope($scope);

				if ($leftType->isNull()->yes()) {
					return $rightType;
				}

				if (!TypeCombinator::containsNull($leftType)) {
					return $leftType;
				}

				return TypeCombinator::union(
					TypeCombinator::removeNull($leftType),
					$rightType,
				);
			},
			hasYield: $condResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $condResult->isAlwaysTerminating(),
			throwPoints: array_merge($condResult->getThrowPoints(), $rightResult->getThrowPoints()),
			impurePoints: array_merge($condResult->getImpurePoints(), $rightResult->getImpurePoints()),
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
