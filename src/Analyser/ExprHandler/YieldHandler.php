<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use function array_merge;

/**
 * @implements ExprHandler<Yield_>
 */
#[AutowiredService]
final class YieldHandler implements ExprHandler
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Yield_;
	}

	public function processExpr(Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$throwPoints = [
			InternalThrowPoint::createImplicit($scope, $expr),
		];
		$impurePoints = [
			new ImpurePoint(
				$scope,
				$expr,
				'yield',
				'yield',
				true,
			),
		];
		$isAlwaysTerminating = false;
		if ($expr->key !== null) {
			$keyResult = $this->nodeScopeResolver->processExprNode($stmt, $expr->key, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $keyResult->getScope();
			$throwPoints = $keyResult->getThrowPoints();
			$impurePoints = array_merge($impurePoints, $keyResult->getImpurePoints());
			$isAlwaysTerminating = $keyResult->isAlwaysTerminating();
		}
		if ($expr->value !== null) {
			$valueResult = $this->nodeScopeResolver->processExprNode($stmt, $expr->value, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $valueResult->getScope();
			$throwPoints = array_merge($throwPoints, $valueResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $valueResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $valueResult->isAlwaysTerminating();
		}

		return new ExpressionResult(
			$scope,
			hasYield: true,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
