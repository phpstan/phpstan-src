<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function count;
use function in_array;
use function is_int;

#[AutowiredService]
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
				$startIndex = count($argTypes);

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
					for ($i = $startIndex; $i < count($argTypes); $i++) {
						$optionalArgTypes[] = $i;
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
			$unsealedKeys = [];
			$unsealedValues = [];
			foreach ($argTypes as $argIndex => $argType) {
				$isOptionalArg = in_array($argIndex, $optionalArgTypes, true);

				/** @var array<int|string, ConstantIntegerType|ConstantStringType> $keyTypes */
				$keyTypes = [];
				foreach ($argType->getConstantArrays() as $constantArray) {
					foreach ($constantArray->getKeyTypes() as $keyType) {
						$keyTypes[$keyType->getValue()] = $keyType;
					}
					if ($constantArray->isUnsealed()->yes()) {
						$unsealedTypes = $constantArray->getUnsealedTypes();
						if ($unsealedTypes !== null) {
							$unsealedKeys[] = $unsealedTypes[0];
							$unsealedValues[] = $unsealedTypes[1];
						}
					}
				}

				foreach ($keyTypes as $keyType) {
					$newArrayBuilder->setOffsetValueType(
						$keyType instanceof ConstantIntegerType ? null : $keyType,
						$argType->getOffsetValueType($keyType),
						$isOptionalArg || !$argType->hasOffsetValueType($keyType)->yes(),
					);
				}
			}

			if (count($unsealedKeys) > 0) {
				// Union all input unsealed slots — extras can come from
				// any of the merged arrays at otherwise-unmentioned keys.
				$newArrayBuilder->makeUnsealed(
					TypeCombinator::union(...$unsealedKeys),
					TypeCombinator::union(...$unsealedValues),
				);
			}

			return $newArrayBuilder->getArray();
		}

		$offsetTypes = [];
		foreach ($argTypes as $argIndex => $argType) {
			if (in_array($argIndex, $optionalArgTypes, true)) {
				continue;
			}

			$constArrays = $argType->getConstantArrays();
			if ($constArrays !== []) {
				foreach ($constArrays as $constantArray) {
					foreach ($constantArray->getKeyTypes() as $keyType) {
						$hasOffsetValue = TrinaryLogic::createFromBoolean($argType->hasOffsetValueType($keyType)->yes());
						$offsetTypes[$keyType->getValue()] = [
							$hasOffsetValue,
							$argType->getOffsetValueType($keyType),
						];
					}
				}
			} else {
				foreach ($offsetTypes as $key => [$hasOffsetValue, $offsetValueType]) {
					// more precise values-types will be calculated elsewhere.
					// just remember the offset key.
					$offsetTypes[$key] = [
						$hasOffsetValue->and(TrinaryLogic::createMaybe()),
						new MixedType(),
					];
				}
			}

			foreach (TypeUtils::getAccessoryTypes($argType) as $accessoryType) {
				if (
					!($accessoryType instanceof HasOffsetType)
					&& !($accessoryType instanceof HasOffsetValueType)
				) {
					continue;
				}

				$offsetType = $accessoryType->getOffsetType();
				$offsetTypes[$offsetType->getValue()] = [
					TrinaryLogic::createYes(),
					$argType->getOffsetValueType($offsetType),
				];
			}
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
			$arrayType = TypeCombinator::intersect($arrayType, new AccessoryArrayListType());
		}
		if ($offsetTypes !== []) {
			$knownOffsetValues = [];
			foreach ($offsetTypes as $key => [$hasOffsetValue, $offsetType]) {
				if (is_int($key)) {
					// int keys will be appended and renumbered.
					// at this point we can't reason about them, because unknown arrays are in the mix.
					continue;
				}
				$keyType = new ConstantStringType($key);

				if ($hasOffsetValue->yes()) {
					// the last string-keyed offset will overwrite previous values
					$hasOffsetType = new HasOffsetValueType(
						$keyType,
						$offsetType,
					);
				} elseif ($hasOffsetValue->maybe()) {
					$hasOffsetType = new HasOffsetType(
						$keyType,
					);
				} else {
					continue;
				}

				$knownOffsetValues[] = $hasOffsetType;
			}
			if ($knownOffsetValues !== []) {
				$arrayType = TypeCombinator::intersect($arrayType, ...$knownOffsetValues);
			}
		}

		return $arrayType;
	}

}
