<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use function count;

/**
 * The array + operator of Type::arrayUnion(). The other operators are on ValueSet.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/OperatorTypeHelper.cpp')]
final class OperatorTypeHelper
{

	public static function arrayUnion(Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
	{
		return self::tryArrayUnion($leftType, $rightType) ?? new ErrorType();
	}

	private static function tryArrayUnion(Type $leftType, Type $rightType): ?Type
	{
		$leftConstantArrays = $leftType->getConstantArrays();
		$rightConstantArrays = $rightType->getConstantArrays();

		$leftCount = count($leftConstantArrays);
		$rightCount = count($rightConstantArrays);
		if ($leftCount > 0 && $rightCount > 0
			&& ($leftCount + $rightCount < ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT)) {
			$resultTypes = [];
			foreach ($rightConstantArrays as $rightConstantArray) {
				foreach ($leftConstantArrays as $leftConstantArray) {
					$newArrayBuilder = ConstantArrayTypeBuilder::createFromConstantArray($rightConstantArray);
					foreach ($leftConstantArray->getKeyTypes() as $i => $leftKeyType) {
						$optional = $leftConstantArray->isOptionalKey($i);
						$valueType = $leftConstantArray->getOffsetValueType($leftKeyType);
						if (!$optional) {
							if ($rightConstantArray->hasOffsetValueType($leftKeyType)->maybe()) {
								$valueType = TypeCombinator::union($valueType, $rightConstantArray->getOffsetValueType($leftKeyType));
							}
						}
						$newArrayBuilder->setOffsetValueType(
							$leftKeyType,
							$valueType,
							$optional,
						);
					}
					$resultTypes[] = $newArrayBuilder->getArray();
				}
			}
			return TypeCombinator::union(...$resultTypes);
		}

		// `array{...} + array<TKey, TValue>` keeps the known keyed prefix and
		// folds the right-hand side into an open `...<TKey, TValue>` tail. This
		// only applies when the left arrays' sealedness is known (bleeding edge);
		// otherwise the legacy behavior below is kept for backward compatibility.
		if ($leftCount > 0 && $rightCount === 0
			&& $leftCount < ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT
			&& $rightType->isArray()->yes()) {
			$allSealednessKnown = true;
			foreach ($leftConstantArrays as $leftConstantArray) {
				if ($leftConstantArray->isUnsealed()->maybe()) {
					$allSealednessKnown = false;
					break;
				}
			}

			if ($allSealednessKnown) {
				$rightKeyType = $rightType->getIterableKeyType();
				$rightValueType = $rightType->getIterableValueType();
				$resultTypes = [];
				foreach ($leftConstantArrays as $leftConstantArray) {
					$newArrayBuilder = ConstantArrayTypeBuilder::createFromConstantArray($leftConstantArray);
					$existingUnsealed = $leftConstantArray->getUnsealedTypes();
					if ($leftConstantArray->isUnsealed()->yes() && $existingUnsealed !== null) {
						$newArrayBuilder->makeUnsealed(
							TypeCombinator::union($existingUnsealed[0], $rightKeyType),
							TypeCombinator::union($existingUnsealed[1], $rightValueType),
						);
					} else {
						$newArrayBuilder->makeUnsealed($rightKeyType, $rightValueType);
					}
					$resultTypes[] = $newArrayBuilder->getArray();
				}
				return TypeCombinator::union(...$resultTypes);
			}
		}

		$leftIsArray = $leftType->isArray();
		$rightIsArray = $rightType->isArray();
		if ($leftIsArray->yes() && $rightIsArray->yes()) {
			if ($leftType->getIterableKeyType()->equals($rightType->getIterableKeyType())) {
				// to preserve BenevolentUnionType
				$keyType = $leftType->getIterableKeyType();
			} else {
				$keyTypes = [];
				foreach ([
					$leftType->getIterableKeyType(),
					$rightType->getIterableKeyType(),
				] as $keyType) {
					$keyTypes[] = $keyType;
				}
				$keyType = TypeCombinator::union(...$keyTypes);
			}

			$leftIterableValueType = $leftType->getIterableValueType();
			$arrayType = new ArrayType(
				$keyType,
				TypeCombinator::union($leftIterableValueType, $rightType->getIterableValueType()),
			);

			$accessories = [];
			if ($leftCount > 0) {
				// Use the first constant array as a reference to list potential offsets.
				// We only need to check the first array because we're looking for offsets that exist in ALL arrays.
				$constantArray = $leftConstantArrays[0];
				foreach ($constantArray->getKeyTypes() as $offsetType) {
					if (!$leftType->hasOffsetValueType($offsetType)->yes()) {
						continue;
					}

					$valueType = $leftType->getOffsetValueType($offsetType);
					$accessories[] = new HasOffsetValueType($offsetType, $valueType);
				}
			}

			if ($rightCount > 0) {
				// Use the first constant array as a reference to list potential offsets.
				// We only need to check the first array because we're looking for offsets that exist in ALL arrays.
				$constantArray = $rightConstantArrays[0];
				foreach ($constantArray->getKeyTypes() as $offsetType) {
					if (!$rightType->hasOffsetValueType($offsetType)->yes()) {
						continue;
					}

					$valueType = TypeCombinator::union($leftIterableValueType, $rightType->getOffsetValueType($offsetType));
					$accessories[] = new HasOffsetValueType($offsetType, $valueType);
				}
			}

			if ($leftType->isIterableAtLeastOnce()->yes() || $rightType->isIterableAtLeastOnce()->yes()) {
				$accessories[] = new NonEmptyArrayType();
			}
			if ($leftType->isList()->yes() && $rightType->isList()->yes()) {
				$accessories[] = new AccessoryArrayListType();
			}

			if (count($accessories) > 0) {
				$arrayType = TypeCombinator::intersect($arrayType, ...$accessories);
			}

			return $arrayType;
		}

		return null;
	}

}
