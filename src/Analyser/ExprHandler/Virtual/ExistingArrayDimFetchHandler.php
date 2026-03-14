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
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<ExistingArrayDimFetch>
 */
#[AutowiredService]
final class ExistingArrayDimFetchHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ExistingArrayDimFetch;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->getVar(), $scope, $storage, $nodeCallback, $context->enterDeep());
		$dimResult = $nodeScopeResolver->processExprNode($stmt, $expr->getDim(), $varResult->getScope(), $storage, $nodeCallback, $context->enterDeep());

		return $this->expressionResultFactory->create(
			$expr,
			$scope,
			typeCallback: static fn (Expr $uninteresting, MutatingScope $scope) => $varResult->getTypeForScope($scope)->getOffsetValueType($dimResult->getTypeForScope($scope)),
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $scope->getType(new Expr\ArrayDimFetch($expr->getVar(), $expr->getDim()));
	}

}
