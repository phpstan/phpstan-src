<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @implements ExprHandler<ArrowFunction>
 */
#[AutowiredService]
final class ArrowFunctionHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ArrowFunction;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$result = $nodeScopeResolver->processArrowFunctionNode($stmt, $expr, $scope, $storage, $nodeCallback, null);

		return new ExpressionResult(
			$result->getScope(),
			hasYield: $result->hasYield(),
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
		);
	}

}
