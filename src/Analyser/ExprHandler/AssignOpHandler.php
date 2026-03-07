<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use DivisionByZeroError;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
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

/**
 * @implements ExprHandler<AssignOp>
 */
#[AutowiredService]
final class AssignOpHandler implements ExprHandler
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof AssignOp;
	}

	public function processExpr(Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$nodeScopeResolver = $this->nodeScopeResolver;
		$result = $nodeScopeResolver->processAssignVar(
			$scope,
			$storage,
			$stmt,
			$expr->var,
			$expr,
			$nodeCallback,
			$context,
			static function (MutatingScope $scope) use ($stmt, $expr, $nodeCallback, $context, $storage, $nodeScopeResolver): ExpressionResult {
				$originalScope = $scope;
				if ($expr instanceof Expr\AssignOp\Coalesce) {
					$scope = $scope->filterByFalseyValue(
						new BinaryOp\NotIdentical($expr->var, new ConstFetch(new Name('null'))),
					);
				}

				$result = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
				if ($expr instanceof Expr\AssignOp\Coalesce) {
					$nodeScopeResolver->storeBeforeScope($storage, $expr, $originalScope);
					return new ExpressionResult(
						$result->getScope()->mergeWith($originalScope),
						$result->hasYield(),
						$result->isAlwaysTerminating(),
						$result->getThrowPoints(),
						$result->getImpurePoints(),
					);
				}

				return $result;
			},
			$expr instanceof Expr\AssignOp\Coalesce,
		);
		if (!$expr instanceof Expr\AssignOp\Coalesce) {
			$nodeScopeResolver->storeBeforeScope($storage, $expr, $scope);
		}
		$scope = $result->getScope();
		$throwPoints = $result->getThrowPoints();
		if (
			($expr instanceof Expr\AssignOp\Div || $expr instanceof Expr\AssignOp\Mod) &&
			!$scope->getType($expr->expr)->toNumber()->isSuperTypeOf(new ConstantIntegerType(0))->no()
		) {
			$throwPoints[] = InternalThrowPoint::createExplicit($scope, new ObjectType(DivisionByZeroError::class), $expr, false);
		}

		return new ExpressionResult(
			$scope,
			hasYield: $result->hasYield(),
			isAlwaysTerminating: $result->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $result->getImpurePoints(),
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
