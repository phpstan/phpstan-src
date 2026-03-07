<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BitwiseNot;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<BitwiseNot>
 */
#[AutowiredService]
final class BitwiseNotHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof BitwiseNot;
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

	/**
	 * @param BitwiseNot $expr
	 */
	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $this->initializerExprTypeResolver->getBitwiseNotType($expr->expr, static fn (Expr $expr): Type => $scope->getType($expr));
	}

}
