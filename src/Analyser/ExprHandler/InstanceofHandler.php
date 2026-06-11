<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_merge;
use function strtolower;

/**
 * @implements ExprHandler<Instanceof_>
 */
#[AutowiredService]
final class InstanceofHandler implements ExprHandler
{

	public function __construct(private ExpressionResultFactory $expressionResultFactory)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Instanceof_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$hasYield = $exprResult->hasYield();
		$throwPoints = $exprResult->getThrowPoints();
		$impurePoints = $exprResult->getImpurePoints();
		$isAlwaysTerminating = $exprResult->isAlwaysTerminating();
		$scope = $exprResult->getScope();
		if (!$expr->class instanceof Name) {
			$classResult = $nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $classResult->getScope();
			$hasYield = $hasYield || $classResult->hasYield();
			$throwPoints = array_merge($throwPoints, $classResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $classResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $classResult->isAlwaysTerminating();
		}

		return $this->expressionResultFactory->create(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

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
			$result = $scope->getType($expr->class)->toObjectTypeForInstanceofCheck();
			$classType = $result->type;
			$uncertainty = $result->uncertainty;
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

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		$exprNode = $expr->expr;
		if ($expr->class instanceof Name) {
			$className = (string) $expr->class;
			$lowercasedClassName = strtolower($className);
			if ($lowercasedClassName === 'self' && $scope->isInClass()) {
				$type = new ObjectType($scope->getClassReflection()->getName());
			} elseif ($lowercasedClassName === 'static' && $scope->isInClass()) {
				$type = new StaticType($scope->getClassReflection());
			} elseif ($lowercasedClassName === 'parent') {
				if (
					$scope->isInClass()
					&& $scope->getClassReflection()->getParentClass() !== null
				) {
					$type = new ObjectType($scope->getClassReflection()->getParentClass()->getName());
				} else {
					$type = new NonexistentParentClassType();
				}
			} else {
				$type = new ObjectType($className);
			}
			return $typeSpecifier->create($exprNode, $type, $context, $scope)->setRootExpr($expr);
		}

		$result = $scope->getType($expr->class)->toObjectTypeForInstanceofCheck();
		$type = $result->type;
		$uncertainty = $result->uncertainty;

		if (!$type->isSuperTypeOf(new MixedType())->yes()) {
			if ($context->true()) {
				$type = TypeCombinator::intersect(
					$type,
					new ObjectWithoutClassType(),
				);
				return $typeSpecifier->create($exprNode, $type, $context, $scope)->setRootExpr($expr);
			} elseif ($context->false() && !$uncertainty) {
				$exprType = $scope->getType($expr->expr);
				if (!$type->isSuperTypeOf($exprType)->yes()) {
					return $typeSpecifier->create($exprNode, $type, $context, $scope)->setRootExpr($expr);
				}
			}
		}
		if ($context->true()) {
			return $typeSpecifier->create($exprNode, new ObjectWithoutClassType(), $context, $scope)->setRootExpr($exprNode);
		}

		return (new SpecifiedTypes([], []))->setRootExpr($expr);
	}

}
