<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @implements ExprHandler<ConstFetch>
 */
#[AutowiredService]
final class ConstFetchHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ConstFetch;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$nodeScopeResolver->callNodeCallback($nodeCallback, $expr->name, $scope, $storage);

		return new ExpressionResult(
			$scope,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
