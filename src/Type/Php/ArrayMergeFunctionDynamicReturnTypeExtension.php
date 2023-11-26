<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_keys;
use function count;
use function in_array;

class ArrayMergeFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_merge';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();

		if (!isset($args[0])) {
			return null;
		}

		$argTypes = [];
		$optionalArgTypes = [];
		$allConstant = true;
		foreach ($args as $arg) {
			$argType = $scope->getType($arg->value);

			if ($arg->unpack) {
				if ($argType instanceof ConstantArrayType) {
					$argTypesFound = $argType->getValueTypes();
				} else {
					$argTypesFound = [$argType->getIterableValueType()];
				}

				foreach ($argTypesFound as $argTypeFound) {
					$argTypes[] = $argTypeFound;
					if ($argTypeFound instanceof ConstantArrayType) {
						continue;
					}
					$allConstant = false;
				}

				if (!$argType->isIterableAtLeastOnce()->yes()) {
					// unpacked params can be empty, making them optional
					$optionalArgTypesOffset = count($argTypes) - 1;
					foreach (array_keys($argTypesFound) as $key) {
						$optionalArgTypes[] = $optionalArgTypesOffset + $key;
					}
				}
			} else {
				$argTypes[] = $argType;
				if (!$argType instanceof ConstantArrayType) {
					$allConstant = false;
				}
			}
		}

		if ($allConstant) {
			$newArrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			foreach ($argTypes as $argType) {
				if (!$argType instanceof ConstantArrayType) {
					throw new ShouldNotHappenException();
				}

				$keyTypes = $argType->getKeyTypes();
				$valueTypes = $argType->getValueTypes();
				$optionalKeys = $argType->getOptionalKeys();

				foreach ($keyTypes as $k => $keyType) {
					$isOptional = in_array($k, $optionalKeys, true);

					$newArrayBuilder->setOffsetValueType(
						$keyType instanceof ConstantIntegerType ? null : $keyType,
						$valueTypes[$k],
						$isOptional,
					);
				}
			}

			return $newArrayBuilder->getArray();
		}

		$keyTypes = [];
		$valueTypes = [];
		$nonEmpty = false;
		$isList = true;
		foreach ($argTypes as $key => $argType) {
			$keyType = $argType->getIterableKeyType();
			$keyTypes[] = $keyType;
			$valueTypes[] = $argType->getIterableValueType();

			if (!(new IntegerType())->isSuperTypeOf($keyType)->yes()) {
				$isList = false;
			}

			if (in_array($key, $optionalArgTypes, true) || !$argType->isIterableAtLeastOnce()->yes()) {
				continue;
			}

			$nonEmpty = true;
		}

		$keyType = TypeCombinator::union(...$keyTypes);
		if ($keyType instanceof NeverType) {
			return new ConstantArrayType([], []);
		}

		$arrayType = new ArrayType(
			$keyType,
			TypeCombinator::union(...$valueTypes),
		);

		if ($nonEmpty) {
			$arrayType = TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}
		if ($isList) {
			$arrayType = AccessoryArrayListType::intersectWith($arrayType);
		}

		return $arrayType;
	}

}
