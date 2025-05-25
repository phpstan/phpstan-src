<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\Variable;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\TypeResult;
use function is_string;

#[AutowiredService]
final class RicherScopeGetTypeHelper
{

	public function __construct(private InitializerExprTypeResolver $initializerExprTypeResolver)
	{
	}

	/**
	 * @return TypeResult<BooleanType>
	 */
	public function getIdenticalResult(Scope $scope, Identical $expr): TypeResult
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

		$leftType = $scope->getType($expr->left);
		$rightType = $scope->getType($expr->right);

		if (!$scope instanceof MutatingScope) {
			return $this->initializerExprTypeResolver->resolveIdenticalType($leftType, $rightType);
		}

		if (
			(
				$expr->left instanceof Node\Expr\PropertyFetch
				|| $expr->left instanceof Node\Expr\StaticPropertyFetch
			)
			&& $rightType->isNull()->yes()
			&& !$scope->hasPropertyNativeType($expr->left)
		) {
			return new TypeResult(new BooleanType(), []);
		}

		if (
			(
				$expr->right instanceof Node\Expr\PropertyFetch
				|| $expr->right instanceof Node\Expr\StaticPropertyFetch
			)
			&& $leftType->isNull()->yes()
			&& !$scope->hasPropertyNativeType($expr->right)
		) {
			return new TypeResult(new BooleanType(), []);
		}

		return $this->initializerExprTypeResolver->resolveIdenticalType($leftType, $rightType);
	}

	/**
	 * @return TypeResult<BooleanType>
	 */
	public function getNotIdenticalResult(Scope $scope, Node\Expr\BinaryOp\NotIdentical $expr): TypeResult
	{
		$identicalResult = $this->getIdenticalResult($scope, new Identical($expr->left, $expr->right));
		$identicalType = $identicalResult->type;
		if ($identicalType instanceof ConstantBooleanType) {
			return new TypeResult(new ConstantBooleanType(!$identicalType->getValue()), $identicalResult->reasons);
		}

		return new TypeResult(new BooleanType(), []);
	}

}
