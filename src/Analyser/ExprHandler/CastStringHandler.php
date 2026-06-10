<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<Cast\String_>
 */
#[AutowiredService]
final class CastStringHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ImplicitToStringCallHelper $implicitToStringCallHelper,
		private TypeSpecifier $typeSpecifier,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Cast\String_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$impurePoints = $exprResult->getImpurePoints();
		$throwPoints = $exprResult->getThrowPoints();

		$toStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($expr->expr, $exprResult->getType(), $scope);
		$throwPoints = array_merge($throwPoints, $toStringResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $toStringResult->getImpurePoints());

		$scope = $exprResult->getScope();

		return new ExpressionResult(
			$scope,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			expr: $expr,
			typeCallback: $this->createTypeCallback($exprResult),
			specifyTypesCallback: function (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx) use ($nodeScopeResolver, $stmt): SpecifiedTypes {
				if (!$e instanceof Cast\String_) {
					throw new ShouldNotHappenException();
				}

				// the old synthetic, processed through the migrated handlers on
				// demand (ResultAwareScope tier 4, unseeded — §3.13)
				$adapterScope = $s->toResultAwareScope([], $nodeScopeResolver, $stmt, new ExpressionResultStorage());

				return $this->typeSpecifier->specifyTypesInCondition(
					$adapterScope,
					new NotEqual($e->expr, new String_('')),
					$ctx,
				)->setRootExpr($e);
			},
		);
	}

	/**
	 * @return callable(Expr, MutatingScope): Type
	 */
	private function createTypeCallback(ExpressionResult $exprResult): callable
	{
		return function (Expr $e, MutatingScope $s) use ($exprResult): Type {
			if (!$e instanceof Cast) {
				throw new ShouldNotHappenException();
			}

			return $this->initializerExprTypeResolver->getCastType($e, static function (Expr $inner) use ($e, $exprResult, $s): Type {
				if ($inner === $e->expr) {
					return $exprResult->getTypeForScope($s);
				}

				return $s->getType($inner);
			});
		};
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $this->initializerExprTypeResolver->getCastType($expr, static fn (Expr $expr): Type => $scope->getType($expr));
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyTypesInCondition(
			$scope,
			new NotEqual($expr->expr, new String_('')),
			$context,
		)->setRootExpr($expr);
	}

}
