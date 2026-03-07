<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use DivisionByZeroError;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ObjectType;
use function array_merge;

/**
 * @implements ExprHandler<BinaryOp>
 */
#[AutowiredService]
final class BinaryOpHandler implements ExprHandler
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof BinaryOp
			&& !$expr instanceof BooleanAnd
			&& !$expr instanceof BinaryOp\LogicalAnd
			&& !$expr instanceof BooleanOr
			&& !$expr instanceof BinaryOp\LogicalOr
			&& !$expr instanceof BinaryOp\Coalesce
			&& !$expr instanceof BinaryOp\Pipe;
	}

	public function processExpr(Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$result = $this->nodeScopeResolver->processExprNode($stmt, $expr->left, $scope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $result->getScope();
		$hasYield = $result->hasYield();
		$throwPoints = $result->getThrowPoints();
		$impurePoints = $result->getImpurePoints();
		$isAlwaysTerminating = $result->isAlwaysTerminating();
		$result = $this->nodeScopeResolver->processExprNode($stmt, $expr->right, $scope, $storage, $nodeCallback, $context->enterDeep());
		if (
			($expr instanceof BinaryOp\Div || $expr instanceof BinaryOp\Mod) &&
			!$scope->getType($expr->right)->toNumber()->isSuperTypeOf(new ConstantIntegerType(0))->no()
		) {
			$throwPoints[] = InternalThrowPoint::createExplicit($scope, new ObjectType(DivisionByZeroError::class), $expr, false);
		}
		$scope = $result->getScope();
		$hasYield = $hasYield || $result->hasYield();
		$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
		$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
