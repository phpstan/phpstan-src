<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use ArrayAccess;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\NullsafeShortCircuitingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<ArrayDimFetch>
 */
#[AutowiredService]
final class ArrayDimFetchHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ArrayDimFetch;
	}

	/**
	 * @param ArrayDimFetch $expr
	 */
	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if ($expr->dim === null) {
			return new NeverType();
		}

		$offsetAccessibleType = $scope->getType($expr->var);
		if ($offsetAccessibleType instanceof NeverType) {
			return NullsafeShortCircuitingHelper::getType($scope, $expr->var, $offsetAccessibleType);
		}

		if (
			!$offsetAccessibleType->isArray()->yes()
			&& (new ObjectType(\ArrayAccess::class))->isSuperTypeOf($offsetAccessibleType)->yes()
		) {
			return NullsafeShortCircuitingHelper::getType(
				$scope,
				$expr->var,
				$scope->getType(
					new MethodCall(
						$expr->var,
						new Identifier('offsetGet'),
						[
							new Arg($expr->dim),
						],
					),
				),
			);
		}

		$offsetType = $scope->getType($expr->dim);
		return NullsafeShortCircuitingHelper::getType(
			$scope,
			$expr->var,
			$offsetAccessibleType->getOffsetValueType($offsetType),
		);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		if ($expr->dim !== null) {
			$result = $nodeScopeResolver->processExprNode($stmt, $expr->dim, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $result->getScope();
		}

		$result = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());
		$hasYield = $hasYield || $result->hasYield();
		$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
		$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
		$scope = $result->getScope();

		$varType = $scope->getType($expr->var);
		if (!$varType->isArray()->yes() && !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->no()) {
			$throwPoints = array_merge($throwPoints, $nodeScopeResolver->processExprNode(
				$stmt,
				new MethodCall($expr->var, 'offsetGet'),
				$scope,
				$storage,
				new NoopNodeCallback(),
				$context,
			)->getThrowPoints());
		}

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
