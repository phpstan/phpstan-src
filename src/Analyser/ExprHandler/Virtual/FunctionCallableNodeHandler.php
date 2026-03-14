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
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<FunctionCallableNode>
 */
#[AutowiredService]
final class FunctionCallableNodeHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof FunctionCallableNode;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$throwPoints = [];
		$impurePoints = [];
		$hasYield = false;
		$isAlwaysTerminating = false;
		if ($expr->getName() instanceof Expr) {
			$result = $nodeScopeResolver->processExprNode($stmt, $expr->getName(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
		}

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		// in practice the type of the first-class callable is resolved
		// by FirstClassCallableFuncCallHandler
		return new MixedType();
	}

}
