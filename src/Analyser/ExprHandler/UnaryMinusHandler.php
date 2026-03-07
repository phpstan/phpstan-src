<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @implements ExprHandler<UnaryMinus>
 */
#[AutowiredService]
final class UnaryMinusHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof UnaryMinus;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$result = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $result->getScope();

		return new ExpressionResult(
			$scope,
			hasYield: $result->hasYield(),
			isAlwaysTerminating: $result->isAlwaysTerminating(),
			throwPoints: $result->getThrowPoints(),
			impurePoints: $result->getImpurePoints(),
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
