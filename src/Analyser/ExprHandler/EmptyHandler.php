<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\Empty_;
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
		private TypeSpecifier $typeSpecifier,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Empty_;
	}

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

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		$isset = $scope->issetCheck($expr->expr, static fn () => true);
		if ($isset === false) {
			return new SpecifiedTypes();
		}

		return $typeSpecifier->specifyTypesInCondition($scope, new BooleanOr(
			new Expr\BooleanNot(new Expr\Isset_([$expr->expr])),
			new Expr\BooleanNot($expr->expr),
		), $context)->setRootExpr($expr);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$nonNullabilityResult = $this->nonNullabilityHelper->ensureNonNullability($scope, $expr->expr, static fn (MutatingScope $askedScope): MutatingScope => $askedScope->toResultAwareScope([], $nodeScopeResolver, $stmt, $storage));
		$scope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $expr->expr);
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $exprResult->getScope();
		$scope = $this->nonNullabilityHelper->revertNonNullability($scope, $nonNullabilityResult->getSpecifiedExpressions());
		$scope = $nodeScopeResolver->lookForUnsetAllowedUndefinedExpressions($scope, $expr->expr);

		return new ExpressionResult(
			$scope,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $exprResult->getThrowPoints(),
			impurePoints: $exprResult->getImpurePoints(),
			expr: $expr,
			typeCallback: static function (Expr $e, MutatingScope $s) use ($nodeScopeResolver, $stmt): Type {
				if (!$e instanceof Expr\Empty_) {
					throw new ShouldNotHappenException();
				}

				// issetCheck() walks the expression asking for types — priced
				// through an unseeded adapter (ResultAwareScope tiers)
				$adapterScope = $s->toResultAwareScope([], $nodeScopeResolver, $stmt, new ExpressionResultStorage());
				$result = $adapterScope->issetCheck($e->expr, static function (Type $type): ?bool {
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
			},
			// the old specifyTypes() body stays the single source (the BinaryOp
			// precedent) — its `!isset(X) || !X` synthetic routes through the
			// migrated handlers via the adapter's synthetic processing
			specifyTypesCallback: function (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx) use ($nodeScopeResolver, $stmt): SpecifiedTypes {
				if (!$e instanceof Expr\Empty_) {
					throw new ShouldNotHappenException();
				}

				$adapterScope = $s->toResultAwareScope([], $nodeScopeResolver, $stmt, new ExpressionResultStorage());

				return $this->specifyTypes($this->typeSpecifier, $adapterScope, $e, $ctx);
			},
		);
	}

}
