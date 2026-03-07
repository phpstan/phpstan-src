<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Traverser\InstanceOfClassTypeTraverser;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use function array_merge;
use function strtolower;

/**
 * @implements ExprHandler<Instanceof_>
 */
#[AutowiredService]
final class InstanceofHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Instanceof_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$result = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $result->getScope();
		$hasYield = $result->hasYield();
		$throwPoints = $result->getThrowPoints();
		$impurePoints = $result->getImpurePoints();
		$isAlwaysTerminating = $result->isAlwaysTerminating();
		if ($expr->class instanceof Expr) {
			$result = $nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $result->getScope();
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
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

	/**
	 * @param Instanceof_ $expr
	 */
	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$expressionType = $scope->getType($expr->expr);
		if (
			$scope->isInTrait()
			&& TypeUtils::findThisType($expressionType) !== null
		) {
			return new BooleanType();
		}
		if ($expressionType instanceof NeverType) {
			return new ConstantBooleanType(false);
		}

		$uncertainty = false;

		if ($expr->class instanceof Name) {
			$unresolvedClassName = $expr->class->toString();
			if (
				strtolower($unresolvedClassName) === 'static'
				&& $scope->isInClass()
			) {
				$classType = new StaticType($scope->getClassReflection());
			} else {
				$className = $scope->resolveName($expr->class);
				$classType = new ObjectType($className);
			}
		} else {
			$classType = $scope->getType($expr->class);
			$traverser = new InstanceOfClassTypeTraverser();
			$classType = TypeTraverser::map($classType, $traverser);
			$uncertainty = $traverser->getUncertainty();
		}

		if ($classType->isSuperTypeOf(new MixedType())->yes()) {
			return new BooleanType();
		}

		$isSuperType = $classType->isSuperTypeOf($expressionType);

		if ($isSuperType->no()) {
			return new ConstantBooleanType(false);
		} elseif ($isSuperType->yes() && !$uncertainty) {
			return new ConstantBooleanType(true);
		}

		return new BooleanType();
	}

}
