<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_keys;
use function count;
use function in_array;

final class ArrayMergeFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
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
		foreach ($args as $arg) {
			$argType = $scope->getType($arg->value);

			if ($arg->unpack) {
				if ($argType->isConstantArray()->yes()) {
					foreach ($argType->getConstantArrays() as $constantArray) {
						foreach ($constantArray->getValueTypes() as $valueType) {
							$argTypes[] = $valueType;
						}
					}
				} else {
					$argTypes[] = $argType->getIterableValueType();
				}

				if (!$argType->isIterableAtLeastOnce()->yes()) {
					// unpacked params can be empty, making them optional
					$optionalArgTypesOffset = count($argTypes) - 1;
					foreach (array_keys($argTypes) as $key) {
						$optionalArgTypes[] = $optionalArgTypesOffset + $key;
					}
				}
			} else {
				$argTypes[] = $argType;
			}
		}

		$allConstant = TrinaryLogic::createYes()->lazyAnd(
			$argTypes,
			static fn (Type $argType) => $argType->isConstantArray(),
		);

		if ($allConstant->yes()) {
			$newArrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			foreach ($argTypes as $argType) {
				/** @var array<int|string, ConstantIntegerType|ConstantStringType> $keyTypes */
				$keyTypes = [];
				foreach ($argType->getConstantArrays() as $constantArray) {
					foreach ($constantArray->getKeyTypes() as $keyType) {
						$keyTypes[$keyType->getValue()] = $keyType;
					}
				}

				foreach ($keyTypes as $keyType) {
					$newArrayBuilder->setOffsetValueType(
						$keyType instanceof ConstantIntegerType ? null : $keyType,
						$argType->getOffsetValueType($keyType),
						!$argType->hasOffsetValueType($keyType)->yes(),
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
