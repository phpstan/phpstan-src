<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Print_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<Print_>
 */
#[AutowiredService]
final class PrintHandler implements ExprHandler
{

	public function __construct(
		private ImplicitToStringCallHelper $implicitToStringCallHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Print_;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return new ConstantIntegerType(1);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$throwPoints = $exprResult->getThrowPoints();
		$impurePoints = $exprResult->getImpurePoints();

		$toStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($expr->expr, $scope);
		$throwPoints = array_merge($throwPoints, $toStringResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $toStringResult->getImpurePoints());

		$scope = $exprResult->getScope();

		return new ExpressionResult(
			$scope,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: array_merge($impurePoints, [new ImpurePoint($scope, $expr, 'print', 'print', true)]),
		);
	}

}
