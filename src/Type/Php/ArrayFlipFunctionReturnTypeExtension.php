<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class ArrayFlipFunctionReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_flip';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (count($functionCall->args) !== 1) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$array = $functionCall->args[0]->value;
		$argType = $scope->getType($array);

		if ($argType->isArray()->yes()) {
			$keyType = $argType->getIterableKeyType();
			$itemType = $argType->getIterableValueType();

			// make sure items, which get turned into keys contain only valid types
			$itemType = $this->sanitizeConstantArrayKeyTypes($itemType);

			if ($itemType !== null) {
				$flippedArrayType = new ArrayType(
					$itemType,
					$keyType
				);

				if ($argType->isIterableAtLeastOnce()->yes()) {
					$flippedArrayType = TypeCombinator::intersect($flippedArrayType, new NonEmptyArrayType());
				}

				return $flippedArrayType;
			}
		}

		return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
	}

	private function sanitizeConstantArrayKeyTypes(Type $type): Type
	{
		if (
			!$type instanceof IntegerType
			&& !$type instanceof StringType
		) {
			return null;
		}

		return $type;
	}
}
