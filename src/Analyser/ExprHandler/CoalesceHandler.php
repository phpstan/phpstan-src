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
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
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

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($context->null()) {
			return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
		}

		if (!$context->true()) {
			if (!$scope instanceof MutatingScope) {
				throw new ShouldNotHappenException();
			}

			$isset = $scope->issetCheck($expr->left, static fn () => true);

			if ($isset !== true) {
				return new SpecifiedTypes();
			}

			return $typeSpecifier->create(
				$expr->left,
				new NullType(),
				$context->negate(),
				$scope,
			)->setRootExpr($expr);
		}

		if (
			!$context->falsey()
			&& (new ConstantBooleanType(false))->isSuperTypeOf($scope->getType($expr->right)->toBoolean())->yes()
		) {
			return $typeSpecifier->create(
				$expr->left,
				new NullType(),
				TypeSpecifierContext::createFalse(),
				$scope,
			)->setRootExpr($expr);
		}

		// The Coalesce condition matched but produced no narrowing; the legacy
		// if/elseif chain fell through to its empty-SpecifiedTypes tail here,
		// not to the truthy/falsey default.
		return (new SpecifiedTypes([], []))->setRootExpr($expr);
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
