<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
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
		private TypeSpecifier $typeSpecifier,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
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

		if ((new ConstantBooleanType(false))->isSuperTypeOf($scope->getType($expr->right)->toBoolean())->yes()) {
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

	/**
	 * The coalesce's non-truthy narrowing: when the left is provably set, a
	 * non-truthy `left ?? right` narrows the left to null (the right side ran).
	 */
	private function specifyCoalesceFalseyTypes(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, MutatingScope $scope, Coalesce $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		$adapterScope = $scope->toResultAwareScope([], $nodeScopeResolver, $stmt, new ExpressionResultStorage());
		$isset = $adapterScope->issetCheck($expr->left, static fn () => true);

		if ($isset !== true) {
			return new SpecifiedTypes();
		}

		return $this->typeSpecifier->create(
			$expr->left,
			new NullType(),
			$context->negate(),
			$adapterScope,
		)->setRootExpr($expr);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$nonNullabilityResult = $this->nonNullabilityHelper->ensureNonNullability($scope, $expr->left, static fn (MutatingScope $askedScope): MutatingScope => $askedScope->toResultAwareScope([], $nodeScopeResolver, $stmt, $storage));
		$condScope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $expr->left);
		$condResult = $nodeScopeResolver->processExprNode($stmt, $expr->left, $condScope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $this->nonNullabilityHelper->revertNonNullability($condResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());
		$scope = $nodeScopeResolver->lookForUnsetAllowedUndefinedExpressions($scope, $expr->left);

		// the isset(left) synthetic routes through the migrated IssetHandler —
		// processed once, its narrowing applied instead of the guarded filters
		$issetLeftExpr = new Expr\Isset_([$expr->left]);
		$issetResult = $nodeScopeResolver->processExprNode($stmt, $issetLeftExpr, $scope, $storage->duplicate(), new NoopNodeCallback(), ExpressionContext::createDeep());
		// the right side runs when the left is null/unset — the coalesce's own
		// falsey narrowing (left narrowed to null when isset-certain), NOT the
		// isset falsey (which would unset the left and poison its certainty)
		// the coalesce's own falsey narrowing — left narrowed to null when
		// isset-certain. No seeds: the left result was evaluated on the
		// non-nullability-ensured scope, so its memoized type is already
		// null-stripped — originals must resolve from the holders (§3.13)
		$rightScope = $scope->applySpecifiedTypes(
			$this->specifyCoalesceFalseyTypes($nodeScopeResolver, $stmt, $scope, $expr, TypeSpecifierContext::createFalsey()),
			[],
		);
		$rightResult = $nodeScopeResolver->processExprNode($stmt, $expr->right, $rightScope, $storage, $nodeCallback, $context->enterDeep());
		$rightExprType = $rightResult->getType();
		$issetTruthyScope = $scope->applySpecifiedTypes(
			$issetResult->getSpecifiedTypes($scope, TypeSpecifierContext::createTruthy()),
			$issetResult->getExprResultsForApply(),
		);
		if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
			$scope = $issetTruthyScope;
		} else {
			$scope = $issetTruthyScope->mergeWith($rightResult->getScope());
		}

		$typeCallback = function (Expr $e, MutatingScope $s) use ($nodeScopeResolver, $stmt, $condResult, $rightResult, $issetResult): Type {
			if (!$e instanceof Coalesce) {
				throw new ShouldNotHappenException();
			}

			$adapterScope = $s->toResultAwareScope([], $nodeScopeResolver, $stmt, new ExpressionResultStorage());
			$result = $adapterScope->issetCheck($e->left, static function (Type $type): ?bool {
				$isNull = $type->isNull();
				if ($isNull->maybe()) {
					return null;
				}

				return !$isNull->yes();
			});

			if ($result !== null && $result !== false) {
				return TypeCombinator::removeNull($condResult->getTypeOnScope($issetResult->getTruthyScope()));
			}

			$rightType = $rightResult->getTypeForScope($s);

			if ($result === null) {
				return TypeCombinator::union(
					TypeCombinator::removeNull($condResult->getTypeOnScope($issetResult->getTruthyScope())),
					$rightType,
				);
			}

			return $rightType;
		};

		$specifyTypesCallback = function (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx) use ($nodeScopeResolver, $stmt, $rightResult): SpecifiedTypes {
			if (!$e instanceof Coalesce) {
				throw new ShouldNotHappenException();
			}

			if ($ctx->null()) {
				return $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx);
			}

			$adapterScope = $s->toResultAwareScope([], $nodeScopeResolver, $stmt, new ExpressionResultStorage());

			if (!$ctx->true()) {
				return $this->specifyCoalesceFalseyTypes($nodeScopeResolver, $stmt, $s, $e, $ctx);
			}

			if ((new ConstantBooleanType(false))->isSuperTypeOf($rightResult->getTypeForScope($s)->toBoolean())->yes()) {
				return $this->typeSpecifier->create(
					$e->left,
					new NullType(),
					TypeSpecifierContext::createFalse(),
					$adapterScope,
				)->setRootExpr($e);
			}

			// The Coalesce condition matched but produced no narrowing; the legacy
			// if/elseif chain fell through to its empty-SpecifiedTypes tail here,
			// not to the truthy/falsey default.
			return (new SpecifiedTypes([], []))->setRootExpr($e);
		};

		return new ExpressionResult(
			$scope,
			hasYield: $condResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $condResult->isAlwaysTerminating(),
			throwPoints: array_merge($condResult->getThrowPoints(), $rightResult->getThrowPoints()),
			impurePoints: array_merge($condResult->getImpurePoints(), $rightResult->getImpurePoints()),
			expr: $expr,
			typeCallback: $typeCallback,
			specifyTypesCallback: $specifyTypesCallback,
		);
	}

}
