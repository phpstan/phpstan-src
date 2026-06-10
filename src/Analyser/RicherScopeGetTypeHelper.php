<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\Variable;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeResult;
use function is_string;

#[AutowiredService]
final class RicherScopeGetTypeHelper
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private PropertyReflectionFinder $propertyReflectionFinder,
	)
	{
	}

	/**
	 * @return TypeResult<BooleanType>
	 */
	public function getIdenticalResult(Scope $scope, Identical $expr): TypeResult
	{
		return $this->getIdenticalResultFromTypes($scope, $expr, $scope->getType($expr->left), $scope->getType($expr->right));
	}

	/**
	 * Type-taking variant for the new world: operand types come from their
	 * ExpressionResults, the scope only answers property-reflection lookups.
	 *
	 * @return TypeResult<BooleanType>
	 */
	public function getIdenticalResultFromTypes(Scope $scope, Identical $expr, Type $leftType, Type $rightType): TypeResult
	{
		if (
			$expr->left instanceof Variable
			&& is_string($expr->left->name)
			&& $expr->right instanceof Variable
			&& is_string($expr->right->name)
			&& $expr->left->name === $expr->right->name
		) {
			return new TypeResult(new ConstantBooleanType(true), []);
		}

		if (
			(
				$expr->left instanceof Node\Expr\PropertyFetch
				|| $expr->left instanceof Node\Expr\StaticPropertyFetch
			)
			&& $rightType->isNull()->yes()
		) {
			$foundPropertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($expr->left, $scope);
			foreach ($foundPropertyReflections as $foundPropertyReflection) {
				if ($foundPropertyReflection->isNative() && !$foundPropertyReflection->hasNativeType()) {
					return new TypeResult(new BooleanType(), []);
				}
			}
		}

		if (
			(
				$expr->right instanceof Node\Expr\PropertyFetch
				|| $expr->right instanceof Node\Expr\StaticPropertyFetch
			)
			&& $leftType->isNull()->yes()
		) {
			$foundPropertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($expr->right, $scope);
			foreach ($foundPropertyReflections as $foundPropertyReflection) {
				if ($foundPropertyReflection->isNative() && !$foundPropertyReflection->hasNativeType()) {
					return new TypeResult(new BooleanType(), []);
				}
			}
		}

		return $this->initializerExprTypeResolver->resolveIdenticalType($leftType, $rightType);
	}

	/**
	 * @return TypeResult<BooleanType>
	 */
	public function getNotIdenticalResult(Scope $scope, Node\Expr\BinaryOp\NotIdentical $expr): TypeResult
	{
		return $this->getNotIdenticalResultFromTypes($scope, $expr, $scope->getType($expr->left), $scope->getType($expr->right));
	}

	/**
	 * @return TypeResult<BooleanType>
	 */
	public function getNotIdenticalResultFromTypes(Scope $scope, Node\Expr\BinaryOp\NotIdentical $expr, Type $leftType, Type $rightType): TypeResult
	{
		$identicalResult = $this->getIdenticalResultFromTypes($scope, new Identical($expr->left, $expr->right), $leftType, $rightType);
		$identicalType = $identicalResult->type;
		if ($identicalType instanceof ConstantBooleanType) {
			return new TypeResult(new ConstantBooleanType(!$identicalType->getValue()), $identicalResult->reasons);
		}

		return new TypeResult(new BooleanType(), []);
	}

}
