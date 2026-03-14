<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Print_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
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
		private ExpressionResultFactory $expressionResultFactory,
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
		$scope = $exprResult->getScope();

		return $this->expressionResultFactory->create(
			$expr,
			$scope,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $exprResult->getThrowPoints(),
			impurePoints: array_merge($exprResult->getImpurePoints(), [new ImpurePoint($scope, $expr, 'print', 'print', true)]),
		);
	}

}
