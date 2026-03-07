<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use function array_merge;
use function count;
use function is_string;

/**
 * @implements ExprHandler<Assign|AssignRef>
 */
#[AutowiredService]
final class AssignHandler implements ExprHandler
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Assign || $expr instanceof AssignRef;
	}

	public function processExpr(Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$nodeScopeResolver = $this->nodeScopeResolver;
		$result = $nodeScopeResolver->processAssignVar(
			$scope,
			$storage,
			$stmt,
			$expr->var,
			$expr->expr,
			$nodeCallback,
			$context,
			static function (MutatingScope $scope) use ($stmt, $expr, $nodeCallback, $context, $storage, $nodeScopeResolver): ExpressionResult {
				$impurePoints = [];
				if ($expr instanceof AssignRef) {
					$referencedExpr = $expr->expr;
					while ($referencedExpr instanceof ArrayDimFetch) {
						$referencedExpr = $referencedExpr->var;
					}

					if ($referencedExpr instanceof PropertyFetch || $referencedExpr instanceof StaticPropertyFetch) {
						$impurePoints[] = new ImpurePoint(
							$scope,
							$expr,
							'propertyAssignByRef',
							'property assignment by reference',
							false,
						);
					}

					$scope = $scope->enterExpressionAssign($expr->expr);
				}

				if ($expr->var instanceof Variable && is_string($expr->var->name)) {
					$context = $context->enterRightSideAssign(
						$expr->var->name,
						$expr->expr,
					);
				}

				$nodeScopeResolver->storeBeforeScope($storage, $expr, $scope);
				$result = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$isAlwaysTerminating = $result->isAlwaysTerminating();
				$scope = $result->getScope();

				if ($expr instanceof AssignRef) {
					$scope = $scope->exitExpressionAssign($expr->expr);
				}

				return new ExpressionResult($scope, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints);
			},
			true,
		);
		$scope = $result->getScope();
		$vars = $nodeScopeResolver->getAssignedVariables($expr->var);
		if (count($vars) > 0) {
			$varChangedScope = false;
			$scope = $nodeScopeResolver->processVarAnnotation($scope, $vars, $stmt, $varChangedScope);
			if (!$varChangedScope) {
				$scope = $nodeScopeResolver->processStmtVarAnnotation($scope, $storage, $stmt, null, $nodeCallback);
			}
		}

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

}
