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
	public function getIdenticalResult(Scope $scope, Identical $expr, ?NodeScopeResolver $nodeScopeResolver = null, ?Type $leftType = null, ?Type $rightType = null): TypeResult
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

		// operand types passed from inside-out callbacks (e.g. BinaryOp's
		// typeCallback) come from the already computed ExpressionResults;
		// $nodeScopeResolver reads them from storage instead of Scope::getType();
		// rules call this with neither (BC).
		$leftType ??= $nodeScopeResolver !== null
			? $nodeScopeResolver->readTypeOfMaybeStored($expr->left, $scope->toMutatingScope())
			: $scope->getType($expr->left);
		$rightType ??= $nodeScopeResolver !== null
			? $nodeScopeResolver->readTypeOfMaybeStored($expr->right, $scope->toMutatingScope())
			: $scope->getType($expr->right);

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
	public function getNotIdenticalResult(Scope $scope, Node\Expr\BinaryOp\NotIdentical $expr, ?NodeScopeResolver $nodeScopeResolver = null, ?Type $leftType = null, ?Type $rightType = null): TypeResult
	{
		$identicalResult = $this->getIdenticalResult($scope, new Identical($expr->left, $expr->right), $nodeScopeResolver, $leftType, $rightType);
		$identicalType = $identicalResult->type;
		if ($identicalType instanceof ConstantBooleanType) {
			return new TypeResult(new ConstantBooleanType(!$identicalType->getValue()), $identicalResult->reasons);
		}

		return new TypeResult(new BooleanType(), []);
	}

}
