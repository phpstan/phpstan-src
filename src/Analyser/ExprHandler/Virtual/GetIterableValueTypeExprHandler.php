<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\GetIterableValueTypeExpr;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<GetIterableValueTypeExpr>
 */
#[AutowiredService]
final class GetIterableValueTypeExprHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof GetIterableValueTypeExpr;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$innerResult = $nodeScopeResolver->processExprNode($stmt, $expr->getExpr(), $scope, $storage, $nodeCallback, $context->enterDeep());

		return $this->expressionResultFactory->create(
			$expr,
			$scope,
			typeCallback: static fn (Expr $uninteresting, MutatingScope $scope) => $scope->getIterableValueType($innerResult->getTypeForScope($scope)),
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $scope->getIterableValueType($scope->getType($expr->getExpr()));
	}

}
