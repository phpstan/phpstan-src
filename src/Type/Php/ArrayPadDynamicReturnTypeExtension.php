<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

#[AutowiredService]
final class ArrayPadDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_pad';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (!isset($functionCall->getArgs()[2])) {
			return null;
		}

		$arrayType = $scope->getType($functionCall->getArgs()[0]->value);
		$itemType = $scope->getType($functionCall->getArgs()[2]->value);

		$returnType = new ArrayType(
			TypeCombinator::union($arrayType->getIterableKeyType(), new IntegerType()),
			TypeCombinator::union($arrayType->getIterableValueType(), $itemType),
		);

		$lengthType = $scope->getType($functionCall->getArgs()[1]->value);
		if (
			$arrayType->isIterableAtLeastOnce()->yes()
			|| $lengthType->isSuperTypeOf(new ConstantIntegerType(0))->no()
		) {
			$returnType = TypeCombinator::intersect($returnType, new NonEmptyArrayType());
		}

		if ($arrayType->isList()->yes()) {
			$returnType = TypeCombinator::intersect($returnType, new AccessoryArrayListType());
		}

		return $returnType;
	}

}
