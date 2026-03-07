<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use function array_merge;

/**
 * @implements ExprHandler<StaticPropertyFetch>
 */
#[AutowiredService]
final class StaticPropertyFetchHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof StaticPropertyFetch;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [
			new ImpurePoint(
				$scope,
				$expr,
				'staticPropertyAccess',
				'static property access',
				true,
			),
		];
		$isAlwaysTerminating = false;
		if ($expr->class instanceof Expr) {
			$result = $nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $result->getScope();
		}
		if ($expr->name instanceof Expr) {
			$result = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();
		}

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
