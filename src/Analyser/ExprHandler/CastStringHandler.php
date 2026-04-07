<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
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

		$toStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($expr->expr, $scope);
		$throwPoints = array_merge($throwPoints, $toStringResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $toStringResult->getImpurePoints());

		$scope = $exprResult->getScope();

		return new ExpressionResult(
			$scope,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $this->initializerExprTypeResolver->getCastType($expr, static fn (Expr $expr): Type => $scope->getType($expr));
	}

}
