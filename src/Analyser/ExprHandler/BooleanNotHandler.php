<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
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
 * @implements ExprHandler<BooleanNot>
 */
#[AutowiredService]
final class BooleanNotHandler implements ExprHandler
{

	public function __construct(
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private TypeSpecifier $typeSpecifier,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof BooleanNot;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $exprResult->getScope();

		$typeCallback = static function (Expr $e, MutatingScope $s) use ($exprResult): Type {
			if (!$e instanceof BooleanNot) {
				throw new ShouldNotHappenException();
			}

			$exprBooleanType = $exprResult->getTypeForScope($s)->toBoolean();
			if ($exprBooleanType->isTrue()->yes()) {
				return new ConstantBooleanType(false);
			}
			if ($exprBooleanType->isFalse()->yes()) {
				return new ConstantBooleanType(true);
			}

			return new BooleanType();
		};

		return new ExpressionResult(
			$scope,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $exprResult->getThrowPoints(),
			impurePoints: $exprResult->getImpurePoints(),
			// incremental branch scopes (§3.13): `!X` is truthy exactly when X is
			// falsey — the inner result's branch scopes, swapped
			truthyScopeCallback: static fn (): MutatingScope => $exprResult->getFalseyScope(),
			falseyScopeCallback: static fn (): MutatingScope => $exprResult->getTruthyScope(),
			expr: $expr,
			typeCallback: $typeCallback,
			specifyTypesCallback: $this->createSpecifyTypesCallback($nodeScopeResolver, $stmt, $exprResult),
		);
	}

	/**
	 * New-world copy of specifyTypes(): the inner expression's narrowing with
	 * the context negated; a not-yet-migrated inner takes the old-world
	 * dispatcher with an unseeded adapter (the inner must be evaluated on the
	 * ask scope, §3.13).
	 *
	 * @return callable(Expr, MutatingScope, TypeSpecifierContext): SpecifiedTypes
	 */
	private function createSpecifyTypesCallback(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, ExpressionResult $exprResult): callable
	{
		return function (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx) use ($nodeScopeResolver, $stmt, $exprResult): SpecifiedTypes {
			if (!$e instanceof BooleanNot) {
				throw new ShouldNotHappenException();
			}

			if ($ctx->null()) {
				return $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx);
			}

			if ($exprResult->hasSpecifiedTypesCallback()) {
				return $exprResult->getSpecifiedTypes($s, $ctx->negate())->setRootExpr($e);
			}

			$adapterScope = $s->toResultAwareScope([], $nodeScopeResolver, $stmt, new ExpressionResultStorage());

			return $this->typeSpecifier->specifyTypesInCondition($adapterScope, $e->expr, $ctx->negate())->setRootExpr($e);
		};
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$exprBooleanType = $scope->getType($expr->expr)->toBoolean();
		if ($exprBooleanType instanceof ConstantBooleanType) {
			return new ConstantBooleanType(!$exprBooleanType->getValue());
		}

		return new BooleanType();
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($context->null()) {
			return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
		}

		return $typeSpecifier->specifyTypesInCondition($scope, $expr->expr, $context->negate())->setRootExpr($expr);
	}

}
