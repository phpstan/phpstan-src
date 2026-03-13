<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<AlwaysRememberedExpr>
 */
#[AutowiredService]
final class AlwaysRememberedExprHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof AlwaysRememberedExpr;
	}

	public function processExpr(
		NodeScopeResolver $nodeScopeResolver,
		Stmt $stmt,
		Expr $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
	): ExpressionResult
	{
		$innerExpr = $expr->getExpr();
		$result = $nodeScopeResolver->processExprNode($stmt, $innerExpr, $scope, $storage, $nodeCallback, $context);
		$scope = $result->getScope();

		return new ExpressionResult(
			$scope,
			hasYield: $result->hasYield(),
			isAlwaysTerminating: $result->isAlwaysTerminating(),
			throwPoints: $result->getThrowPoints(),
			impurePoints: $result->getImpurePoints(),
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($innerExpr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($innerExpr),
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $scope->nativeTypesPromoted ? $expr->getNativeExprType() : $expr->getExprType();
	}

}
