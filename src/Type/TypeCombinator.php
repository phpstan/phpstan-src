<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateBenevolentUnionType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateUnionType;
use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function array_splice;
use function array_values;
use function count;
use function get_class;
use function is_int;
use function md5;
use function sprintf;
use function usort;

/** @api */
class TypeCombinator
{

	public static function addNull(Type $type): Type
	{
		if ((new NullType())->isSuperTypeOf($type)->no()) {
			return self::union($type, new NullType());
		}

		return $type;
	}

	public static function remove(Type $fromType, Type $typeToRemove): Type
	{
		if ($typeToRemove instanceof UnionType) {
			foreach ($typeToRemove->getTypes() as $unionTypeToRemove) {
				$fromType = self::remove($fromType, $unionTypeToRemove);
			}
			return $fromType;
		}

		$isSuperType = $typeToRemove->isSuperTypeOf($fromType);
		if ($isSuperType->yes()) {
			return new NeverType();
		}
		if ($isSuperType->no()) {
			return $fromType;
		}

		if ($typeToRemove instanceof MixedType) {
			$typeToRemoveSubtractedType = $typeToRemove->getSubtractedType();
			if ($typeToRemoveSubtractedType !== null) {
				return self::intersect($fromType, $typeToRemoveSubtractedType);
			}
		}

		return $fromType->tryRemove($typeToRemove) ?? $fromType;
	}

	public static function removeNull(Type $type): Type
	{
		if (self::containsNull($type)) {
			return self::remove($type, new NullType());
		}

		return $type;
	}

	public static function containsNull(Type $type): bool
	{
		if ($type instanceof UnionType) {
			foreach ($type->getTypes() as $innerType) {
				if ($innerType instanceof NullType) {
					return true;
				}
			}

			return false;
		}

		return $type instanceof NullType;
	}

	public static function union(Type ...$types): Type
	{
		$typesCount = count($types);
		if ($typesCount === 0) {
			return new NeverType();
		}

		$benevolentTypes = [];
		$benevolentUnionObject = null;
		// transform A | (B | C) to A | B | C
		for ($i = 0; $i < $typesCount; $i++) {
			if ($types[$i] instanceof BenevolentUnionType) {
				if ($types[$i] instanceof TemplateBenevolentUnionType && $benevolentUnionObject === null) {
					$benevolentUnionObject = $types[$i];
				}
				$benevolentTypesCount = 0;
				$typesInner = $types[$i]->getTypes();
				foreach ($typesInner as $benevolentInnerType) {
					$benevolentTypesCount++;
					$benevolentTypes[$benevolentInnerType->describe(VerbosityLevel::value())] = $benevolentInnerType;
				}
				array_splice($types, $i, 1, $typesInner);
				$typesCount += $benevolentTypesCount - 1;
				continue;
			}
			if (!($types[$i] instanceof UnionType)) {
				continue;
			}
			if ($types[$i] instanceof TemplateType) {
				continue;
			}

			$typesInner = $types[$i]->getTypes();
			array_splice($types, $i, 1, $typesInner);
			$typesCount += count($typesInner) - 1;
		}

		if ($typesCount === 1) {
			return $types[0];
		}

		$arrayTypes = [];
		$arrayAccessoryTypes = [];
		$scalarTypes = [];
		$hasGenericScalarTypes = [];
		for ($i = 0; $i < $typesCount; $i++) {
			if ($types[$i] instanceof ConstantScalarType) {
				$type = $types[$i];
				$scalarTypes[get_class($type)][md5($type->describe(VerbosityLevel::cache()))] = $type;
				unset($types[$i]);
				continue;
			}
			if ($types[$i] instanceof BooleanType) {
				$hasGenericScalarTypes[ConstantBooleanType::class] = true;
			}
			if ($types[$i] instanceof FloatType) {
				$hasGenericScalarTypes[ConstantFloatType::class] = true;
			}
			if ($types[$i] instanceof IntegerType && !$types[$i] instanceof IntegerRangeType) {
				$hasGenericScalarTypes[ConstantIntegerType::class] = true;
			}
			if ($types[$i] instanceof StringType && !$types[$i] instanceof ClassStringType) {
				$hasGenericScalarTypes[ConstantStringType::class] = true;
			}
			if ($types[$i] instanceof IntersectionType) {
				$intermediateArrayType = null;
				$intermediateAccessoryTypes = [];
				foreach ($types[$i]->getTypes() as $innerType) {
					if ($innerType instanceof TemplateType) {
						continue 2;
					}
					if ($innerType instanceof ArrayType) {
						$intermediateArrayType = $innerType;
						continue;
					}
					if ($innerType instanceof AccessoryType || $innerType instanceof CallableType) {
						if ($innerType instanceof HasOffsetValueType) {
							$intermediateAccessoryTypes[sprintf('hasOffsetValue(%s)', $innerType->getOffsetType()->describe(VerbosityLevel::cache()))][] = $innerType;
							continue;
						}

						$intermediateAccessoryTypes[$innerType->describe(VerbosityLevel::cache())][] = $innerType;
						continue;
					}
				}

				if ($intermediateArrayType !== null) {
					$arrayTypes[] = $intermediateArrayType;
					$arrayAccessoryTypes[] = $intermediateAccessoryTypes;
					unset($types[$i]);
					continue;
				}
			}
			if (!$types[$i] instanceof ArrayType) {
				continue;
			}

			$arrayTypes[] = $types[$i];

			if ($types[$i]->isIterableAtLeastOnce()->yes()) {
				$nonEmpty = new NonEmptyArrayType();
				$arrayAccessoryTypes[] = [$nonEmpty->describe(VerbosityLevel::cache()) => [$nonEmpty]];
			} else {
				$arrayAccessoryTypes[] = [];
			}
			unset($types[$i]);
		}

		foreach ($scalarTypes as $classType => $scalarTypeItems) {
			$scalarTypes[$classType] = array_values($scalarTypeItems);
		}

		/** @var ArrayType[] $arrayTypes */
		$arrayTypes = $arrayTypes;

		$types = array_values(
			array_merge(
				$types,
				self::processArrayTypes($arrayTypes, self::unionCommonTypeMaps($arrayAccessoryTypes)),
			),
		);
		$typesCount = count($types);

		// simplify string[] | int[] to (string|int)[]
		for ($i = 0; $i < $typesCount; $i++) {
			for ($j = $i + 1; $j < $typesCount; $j++) {
				if ($types[$i] instanceof IterableType && $types[$j] instanceof IterableType) {
					$types[$i] = new IterableType(
						self::union($types[$i]->getIterableKeyType(), $types[$j]->getIterableKeyType()),
						self::union($types[$i]->getIterableValueType(), $types[$j]->getIterableValueType()),
					);
					array_splice($types, $j, 1);
					$typesCount--;
					continue 2;
				}
			}
		}

		foreach ($scalarTypes as $classType => $scalarTypeItems) {
			if (isset($hasGenericScalarTypes[$classType])) {
				unset($scalarTypes[$classType]);
				continue;
			}
			if ($classType === ConstantBooleanType::class && count($scalarTypeItems) === 2) {
				$types[] = new BooleanType();
				$typesCount++;
				unset($scalarTypes[$classType]);
				continue;
			}

			$scalarTypeItemsCount = count($scalarTypeItems);
			for ($i = 0; $i < $typesCount; $i++) {
				for ($j = 0; $j < $scalarTypeItemsCount; $j++) {
					$compareResult = self::compareTypesInUnion($types[$i], $scalarTypeItems[$j]);
					if ($compareResult === null) {
						continue;
					}

					[$a, $b] = $compareResult;
					if ($a !== null) {
						$types[$i] = $a;
						array_splice($scalarTypeItems, $j--, 1);
						$scalarTypeItemsCount--;
						continue 1;
					}
					if ($b !== null) {
						$scalarTypeItems[$j] = $b;
						array_splice($types, $i--, 1);
						$typesCount--;
						continue 2;
					}
				}
			}

			$scalarTypes[$classType] = $scalarTypeItems;
		}

		if (count($types) > 16) {
			$newTypes = [];
			foreach ($types as $type) {
				$newTypes[$type->describe(VerbosityLevel::cache())] = $type;
			}
			$types = array_values($newTypes);
			$typesCount = count($types);
		}

		// transform A | A to A
		// transform A | never to A
		for ($i = 0; $i < $typesCount; $i++) {
			for ($j = $i + 1; $j < $typesCount; $j++) {
				$compareResult = self::compareTypesInUnion($types[$i], $types[$j]);
				if ($compareResult === null) {
					continue;
				}

				[$a, $b] = $compareResult;
				if ($a !== null) {
					$types[$i] = $a;
					array_splice($types, $j--, 1);
					$typesCount--;
					continue 1;
				}
				if ($b !== null) {
					$types[$j] = $b;
					array_splice($types, $i--, 1);
					$typesCount--;
					continue 2;
				}
			}
		}

		foreach ($scalarTypes as $scalarTypeItems) {
			foreach ($scalarTypeItems as $scalarType) {
				$types[] = $scalarType;
				$typesCount++;
			}
		}

		if ($typesCount === 0) {
			return new NeverType();
		}
		if ($typesCount === 1) {
			return $types[0];
		}

		if ($benevolentTypes !== []) {
			$tempTypes = $types;
			foreach ($tempTypes as $i => $type) {
				if (!isset($benevolentTypes[$type->describe(VerbosityLevel::value())])) {
					break;
				}

				unset($tempTypes[$i]);
			}

			if ($tempTypes === []) {
				if ($benevolentUnionObject instanceof TemplateBenevolentUnionType) {
					return $benevolentUnionObject->withTypes($types);
				}

				return new BenevolentUnionType($types);
			}
		}

		return new UnionType($types);
	}

	/**
	 * @internal
	 * @param list<array<string, list<Type>>> $commonTypeMaps
	 * @return list<Type>
	 */
	public static function unionCommonTypeMaps(array $commonTypeMaps): array
	{
		$commonTypesKeys = [];
		if (count($commonTypeMaps) > 1) {
			$commonTypesKeys = array_keys(array_intersect_key(...$commonTypeMaps));
		} elseif (count($commonTypeMaps) > 0) {
			$commonTypesKeys = array_keys($commonTypeMaps[0]);
		}

		$types = [];
		foreach ($commonTypesKeys as $commonKey) {
			$typesToUnion = [];
			foreach ($commonTypeMaps as $commonTypeMap) {
				foreach ($commonTypeMap[$commonKey] as $commonType) {
					$typesToUnion[] = $commonType;
				}
			}
			$types[] = self::union(...$typesToUnion);
		}

		return $types;
	}

	/**
	 * @return array{Type, null}|array{null, Type}|null
	 */
	private static function compareTypesInUnion(Type $a, Type $b): ?array
	{
		if ($a instanceof IntegerRangeType) {
			$type = $a->tryUnion($b);
			if ($type !== null) {
				$a = $type;
				return [$a, null];
			}
		}
		if ($b instanceof IntegerRangeType) {
			$type = $b->tryUnion($a);
			if ($type !== null) {
				$b = $type;
				return [null, $b];
			}
		}
		if ($a instanceof IntegerRangeType && $b instanceof IntegerRangeType) {
			return null;
		}
		if ($a instanceof HasOffsetValueType && $b instanceof HasOffsetValueType) {
			if ($a->getOffsetType()->equals($b->getOffsetType())) {
				return [new HasOffsetValueType($a->getOffsetType(), self::union($a->getValueType(), $b->getValueType())), null];
			}
		}
		if ($a instanceof ConstantArrayType && $b instanceof ConstantArrayType) {
			return null;
		}

		if ($a instanceof SubtractableType) {
			$typeWithoutSubtractedTypeA = $a->getTypeWithoutSubtractedType();
			if ($typeWithoutSubtractedTypeA instanceof MixedType && $b instanceof MixedType) {
				$isSuperType = $typeWithoutSubtractedTypeA->isSuperTypeOfMixed($b);
			} else {
				$isSuperType = $typeWithoutSubtractedTypeA->isSuperTypeOf($b);
			}
			if ($isSuperType->yes()) {
				$a = self::intersectWithSubtractedType($a, $b);
				return [$a, null];
			}
		}

		if ($b instanceof SubtractableType) {
			$typeWithoutSubtractedTypeB = $b->getTypeWithoutSubtractedType();
			if ($typeWithoutSubtractedTypeB instanceof MixedType && $a instanceof MixedType) {
				$isSuperType = $typeWithoutSubtractedTypeB->isSuperTypeOfMixed($a);
			} else {
				$isSuperType = $typeWithoutSubtractedTypeB->isSuperTypeOf($a);
			}
			if ($isSuperType->yes()) {
				$b = self::intersectWithSubtractedType($b, $a);
				return [null, $b];
			}
		}

		if ($b->isSuperTypeOf($a)->yes()) {
			return [null, $b];
		}

		if ($a->isSuperTypeOf($b)->yes()) {
			return [$a, null];
		}

		if (
			$a instanceof ConstantStringType
			&& $a->getValue() === ''
			&& ($b->describe(VerbosityLevel::value()) === 'non-empty-string'
			|| $b->describe(VerbosityLevel::value()) === 'non-falsy-string')
		) {
			return [null, new StringType()];
		}

		if (
			$b instanceof ConstantStringType
			&& $b->getValue() === ''
			&& ($a->describe(VerbosityLevel::value()) === 'non-empty-string'
				|| $a->describe(VerbosityLevel::value()) === 'non-falsy-string')
		) {
			return [new StringType(), null];
		}

		if (
			$a instanceof ConstantStringType
			&& $a->getValue() === '0'
			&& $b->describe(VerbosityLevel::value()) === 'non-falsy-string'
		) {
			return [null, new IntersectionType([
				new StringType(),
				new AccessoryNonEmptyStringType(),
			])];
		}

		if (
			$b instanceof ConstantStringType
			&& $b->getValue() === '0'
			&& $a->describe(VerbosityLevel::value()) === 'non-falsy-string'
		) {
			return [new IntersectionType([
				new StringType(),
				new AccessoryNonEmptyStringType(),
			]), null];
		}

		return null;
	}

	private static function unionWithSubtractedType(
		Type $type,
		?Type $subtractedType,
	): Type
	{
		if ($subtractedType === null) {
			return $type;
		}

		if ($type instanceof SubtractableType) {
			if ($type->getSubtractedType() === null) {
				return $type;
			}

			$subtractedType = self::union(
				$type->getSubtractedType(),
				$subtractedType,
			);
			if ($subtractedType instanceof NeverType) {
				$subtractedType = null;
			}

			return $type->changeSubtractedType($subtractedType);
		}

		if ($subtractedType->isSuperTypeOf($type)->yes()) {
			return new NeverType();
		}

		return self::remove($type, $subtractedType);
	}

	private static function intersectWithSubtractedType(
		SubtractableType $a,
		Type $b,
	): Type
	{
		if ($a->getSubtractedType() === null) {
			return $a;
		}

		if ($b instanceof IntersectionType) {
			$subtractableTypes = [];
			foreach ($b->getTypes() as $innerType) {
				if (!$innerType instanceof SubtractableType) {
					continue;
				}

				$subtractableTypes[] = $innerType;
			}

			if (count($subtractableTypes) === 0) {
				return $a->getTypeWithoutSubtractedType();
			}

			$subtractedTypes = [];
			foreach ($subtractableTypes as $subtractableType) {
				if ($subtractableType->getSubtractedType() === null) {
					continue;
				}

				$subtractedTypes[] = $subtractableType->getSubtractedType();
			}

			if (count($subtractedTypes) === 0) {
				return $a->getTypeWithoutSubtractedType();

			}

			$subtractedType = self::union(...$subtractedTypes);
		} elseif ($b instanceof SubtractableType) {
			$subtractedType = $b->getSubtractedType();
			if ($subtractedType === null) {
				return $a->getTypeWithoutSubtractedType();
			}
		} else {
			$subtractedTypeTmp = self::intersect($a->getTypeWithoutSubtractedType(), $a->getSubtractedType());
			if ($b->isSuperTypeOf($subtractedTypeTmp)->yes()) {
				return $a->getTypeWithoutSubtractedType();
			}
			$subtractedType = new MixedType(false, $b);
		}

		$subtractedType = self::intersect(
			$a->getSubtractedType(),
			$subtractedType,
		);
		if ($subtractedType instanceof NeverType) {
			$subtractedType = null;
		}

		return $a->changeSubtractedType($subtractedType);
	}

	/**
	 * @param ArrayType[] $arrayTypes
	 * @param Type[] $accessoryTypes
	 * @return Type[]
	 */
	private static function processArrayTypes(array $arrayTypes, array $accessoryTypes): array
	{
		foreach ($arrayTypes as $arrayType) {
			if (!$arrayType instanceof ConstantArrayType) {
				continue;
			}
			if (count($arrayType->getKeyTypes()) > 0) {
				continue;
			}

			foreach ($accessoryTypes as $i => $accessoryType) {
				if (!$accessoryType instanceof NonEmptyArrayType) {
					continue;
				}

				unset($accessoryTypes[$i]);
				break 2;
			}
		}

		if ($arrayTypes === []) {
			return [];
		}
		if (count($arrayTypes) === 1) {
			return [
				self::intersect(...$arrayTypes, ...$accessoryTypes),
			];
		}

		$keyTypesForGeneralArray = [];
		$valueTypesForGeneralArray = [];
		$generalArrayOccurred = false;
		$constantKeyTypesNumbered = [];

		/** @var int|float $nextConstantKeyTypeIndex */
		$nextConstantKeyTypeIndex = 1;

		foreach ($arrayTypes as $arrayType) {
			if (!$arrayType instanceof ConstantArrayType || $generalArrayOccurred) {
				$keyTypesForGeneralArray[] = $arrayType->getKeyType();
				$valueTypesForGeneralArray[] = $arrayType->getItemType();
				$generalArrayOccurred = true;
				continue;
			}

			foreach ($arrayType->getKeyTypes() as $i => $keyType) {
				$keyTypesForGeneralArray[] = $keyType;
				$valueTypesForGeneralArray[] = $arrayType->getValueTypes()[$i];

				$keyTypeValue = $keyType->getValue();
				if (array_key_exists($keyTypeValue, $constantKeyTypesNumbered)) {
					continue;
				}

				$constantKeyTypesNumbered[$keyTypeValue] = $nextConstantKeyTypeIndex;
				$nextConstantKeyTypeIndex *= 2;
				if (!is_int($nextConstantKeyTypeIndex)) {
					$generalArrayOccurred = true;
					continue;
				}
			}
		}

		if ($generalArrayOccurred) {
			return [
				self::intersect(new ArrayType(
					self::union(...$keyTypesForGeneralArray),
					self::union(...$valueTypesForGeneralArray),
				), ...$accessoryTypes),
			];
		}

		return array_map(
			static fn (Type $arrayType) => self::intersect($arrayType, ...$accessoryTypes),
			self::reduceArrays($arrayTypes),
		);
	}

	/**
	 * @param Type[] $constantArrays
	 * @return Type[]
	 */
	private static function reduceArrays(array $constantArrays): array
	{
		$newArrays = [];
		$arraysToProcess = [];
		$emptyArray = null;
		foreach ($constantArrays as $constantArray) {
			if (!$constantArray instanceof ConstantArrayType) {
				$newArrays[] = $constantArray;
				continue;
			}

			if ($constantArray->isEmpty()) {
				$emptyArray = $constantArray;
				continue;
			}

			$arraysToProcess[] = $constantArray;
		}

		if ($emptyArray !== null) {
			$newArrays[] = $emptyArray;
		}

		$arraysToProcessPerKey = [];
		foreach ($arraysToProcess as $i => $arrayToProcess) {
			foreach ($arrayToProcess->getKeyTypes() as $keyType) {
				$arraysToProcessPerKey[$keyType->getValue()][] = $i;
			}
		}

		$eligibleCombinations = [];

		foreach ($arraysToProcessPerKey as $arrays) {
			for ($i = 0, $arraysCount = count($arrays); $i < $arraysCount - 1; $i++) {
				for ($j = $i + 1; $j < $arraysCount; $j++) {
					$eligibleCombinations[$arrays[$i]][$arrays[$j]] = $arrays[$j];
				}
			}
		}

		foreach ($eligibleCombinations as $i => $other) {
			if (!array_key_exists($i, $arraysToProcess)) {
				continue;
			}

			foreach ($other as $j) {
				if (!array_key_exists($j, $arraysToProcess)) {
					continue;
				}

				if ($arraysToProcess[$j]->isKeysSupersetOf($arraysToProcess[$i])) {
					$arraysToProcess[$j] = $arraysToProcess[$j]->mergeWith($arraysToProcess[$i]);
					unset($arraysToProcess[$i]);
					continue 2;

				} elseif ($arraysToProcess[$i]->isKeysSupersetOf($arraysToProcess[$j])) {
					$arraysToProcess[$i] = $arraysToProcess[$i]->mergeWith($arraysToProcess[$j]);
					unset($arraysToProcess[$j]);
					continue 1;
				}
			}
		}

		return array_merge($newArrays, $arraysToProcess);
	}

	public static function intersect(Type ...$types): Type
	{
		$types = array_values($types);

		$sortTypes = static function (Type $a, Type $b): int {
			if (!$a instanceof UnionType || !$b instanceof UnionType) {
				return 0;
			}

			if ($a instanceof TemplateType) {
				return -1;
			}
			if ($b instanceof TemplateType) {
				return 1;
			}

			if ($a instanceof BenevolentUnionType) {
				return -1;
			}
			if ($b instanceof BenevolentUnionType) {
				return 1;
			}

			return 0;
		};
		usort($types, $sortTypes);
		// transform A & (B | C) to (A & B) | (A & C)
		foreach ($types as $i => $type) {
			if (!$type instanceof UnionType) {
				continue;
			}

			$topLevelUnionSubTypes = [];
			$innerTypes = $type->getTypes();
			usort($innerTypes, $sortTypes);
			$slice1 = array_slice($types, 0, $i);
			$slice2 = array_slice($types, $i + 1);
			foreach ($innerTypes as $innerUnionSubType) {
				$topLevelUnionSubTypes[] = self::intersect(
					$innerUnionSubType,
					...$slice1,
					...$slice2,
				);
			}

			$union = self::union(...$topLevelUnionSubTypes);
			if ($union instanceof NeverType) {
				return $union;
			}

			if ($type instanceof BenevolentUnionType) {
				$union = TypeUtils::toBenevolentUnion($union);
			}

			if ($type instanceof TemplateUnionType || $type instanceof TemplateBenevolentUnionType) {
				$union = TemplateTypeFactory::create(
					$type->getScope(),
					$type->getName(),
					$union,
					$type->getVariance(),
					$type->getStrategy(),
				);
			}

			return $union;
		}
		$typesCount = count($types);

		// transform A & (B & C) to A & B & C
		for ($i = 0; $i < $typesCount; $i++) {
			$type = $types[$i];

			if (!($type instanceof IntersectionType)) {
				continue;
			}

			array_splice($types, $i--, 1, $type->getTypes());
			$typesCount = count($types);
		}

		$hasOffsetValueTypeCount = 0;
		$newTypes = [];
		foreach ($types as $type) {
			if (!$type instanceof HasOffsetValueType) {
				$newTypes[] = $type;
				continue;
			}

			$hasOffsetValueTypeCount++;
		}

		if ($hasOffsetValueTypeCount > 32) {
			$newTypes[] = new OversizedArrayType();
			$types = array_values($newTypes);
			$typesCount = count($types);
		}

		usort($types, static function (Type $a, Type $b): int {
			// move subtractables with subtracts before those without to avoid loosing them in the union logic
			if ($a instanceof SubtractableType && $a->getSubtractedType() !== null) {
				return -1;
			}
			if ($b instanceof SubtractableType && $b->getSubtractedType() !== null) {
				return 1;
			}

			if ($a instanceof ConstantArrayType && !$b instanceof ConstantArrayType) {
				return -1;
			}
			if ($b instanceof ConstantArrayType && !$a instanceof ConstantArrayType) {
				return 1;
			}

			return 0;
		});

		// transform IntegerType & ConstantIntegerType to ConstantIntegerType
		// transform Child & Parent to Child
		// transform Object & ~null to Object
		// transform A & A to A
		// transform int[] & string to never
		// transform callable & int to never
		// transform A & ~A to never
		// transform int & string to never
		for ($i = 0; $i < $typesCount; $i++) {
			for ($j = $i + 1; $j < $typesCount; $j++) {
				if ($types[$j] instanceof SubtractableType) {
					$typeWithoutSubtractedTypeA = $types[$j]->getTypeWithoutSubtractedType();

					if ($typeWithoutSubtractedTypeA instanceof MixedType && $types[$i] instanceof MixedType) {
						$isSuperTypeSubtractableA = $typeWithoutSubtractedTypeA->isSuperTypeOfMixed($types[$i]);
					} else {
						$isSuperTypeSubtractableA = $typeWithoutSubtractedTypeA->isSuperTypeOf($types[$i]);
					}
					if ($isSuperTypeSubtractableA->yes()) {
						$types[$i] = self::unionWithSubtractedType($types[$i], $types[$j]->getSubtractedType());
						array_splice($types, $j--, 1);
						$typesCount--;
						continue 1;
					}
				}

				if ($types[$i] instanceof SubtractableType) {
					$typeWithoutSubtractedTypeB = $types[$i]->getTypeWithoutSubtractedType();

					if ($typeWithoutSubtractedTypeB instanceof MixedType && $types[$j] instanceof MixedType) {
						$isSuperTypeSubtractableB = $typeWithoutSubtractedTypeB->isSuperTypeOfMixed($types[$j]);
					} else {
						$isSuperTypeSubtractableB = $typeWithoutSubtractedTypeB->isSuperTypeOf($types[$j]);
					}
					if ($isSuperTypeSubtractableB->yes()) {
						$types[$j] = self::unionWithSubtractedType($types[$j], $types[$i]->getSubtractedType());
						array_splice($types, $i--, 1);
						$typesCount--;
						continue 2;
					}
				}

				if ($types[$i] instanceof IntegerRangeType) {
					$intersectionType = $types[$i]->tryIntersect($types[$j]);
					if ($intersectionType !== null) {
						$types[$j] = $intersectionType;
						array_splice($types, $i--, 1);
						$typesCount--;
						continue 2;
					}
				}

				if ($types[$j] instanceof IterableType) {
					$isSuperTypeA = $types[$j]->isSuperTypeOfMixed($types[$i]);
				} else {
					$isSuperTypeA = $types[$j]->isSuperTypeOf($types[$i]);
				}

				if ($isSuperTypeA->yes()) {
					array_splice($types, $j--, 1);
					$typesCount--;
					continue;
				}

				if ($types[$i] instanceof IterableType) {
					$isSuperTypeB = $types[$i]->isSuperTypeOfMixed($types[$j]);
				} else {
					$isSuperTypeB = $types[$i]->isSuperTypeOf($types[$j]);
				}

				if ($isSuperTypeB->maybe()) {
					if ($types[$i] instanceof ConstantArrayType && $types[$j] instanceof HasOffsetType) {
						$types[$i] = $types[$i]->makeOffsetRequired($types[$j]->getOffsetType());
						array_splice($types, $j--, 1);
						$typesCount--;
						continue;
					}

					if ($types[$j] instanceof ConstantArrayType && $types[$i] instanceof HasOffsetType) {
						$types[$j] = $types[$j]->makeOffsetRequired($types[$i]->getOffsetType());
						array_splice($types, $i--, 1);
						$typesCount--;
						continue 2;
					}

					if ($types[$i] instanceof ConstantArrayType && $types[$j] instanceof HasOffsetValueType) {
						$offsetType = $types[$j]->getOffsetType();
						$valueType = $types[$j]->getValueType();
						$newValueType = self::intersect($types[$i]->getOffsetValueType($offsetType), $valueType);
						if ($newValueType instanceof NeverType) {
							return new NeverType();
						}
						$types[$i] = $types[$i]->setOffsetValueType($offsetType, $newValueType);
						array_splice($types, $j--, 1);
						$typesCount--;
						continue;
					}

					if ($types[$j] instanceof ConstantArrayType && $types[$i] instanceof HasOffsetValueType) {
						$offsetType = $types[$i]->getOffsetType();
						$valueType = $types[$i]->getValueType();
						$newValueType = self::intersect($types[$j]->getOffsetValueType($offsetType), $valueType);
						if ($newValueType instanceof NeverType) {
							return new NeverType();
						}

						$types[$j] = $types[$j]->setOffsetValueType($offsetType, $newValueType);
						array_splice($types, $i--, 1);
						$typesCount--;
						continue 2;
					}

					if ($types[$i] instanceof OversizedArrayType && $types[$j] instanceof HasOffsetValueType) {
						array_splice($types, $j--, 1);
						$typesCount--;
						continue;
					}

					if ($types[$j] instanceof OversizedArrayType && $types[$i] instanceof HasOffsetValueType) {
						array_splice($types, $i--, 1);
						$typesCount--;
						continue 2;
					}

					if ($types[$i] instanceof ConstantArrayType && $types[$j] instanceof ArrayType && !$types[$j] instanceof ConstantArrayType) {
						$newArray = ConstantArrayTypeBuilder::createEmpty();
						$valueTypes = $types[$i]->getValueTypes();
						foreach ($types[$i]->getKeyTypes() as $k => $keyType) {
							$newArray->setOffsetValueType(
								self::intersect($keyType, $types[$j]->getIterableKeyType()),
								self::intersect($valueTypes[$k], $types[$j]->getIterableValueType()),
								$types[$i]->isOptionalKey($k),
							);
						}
						$types[$i] = $newArray->getArray();
						array_splice($types, $j--, 1);
						$typesCount--;
						continue 2;
					}

					if (
						($types[$i] instanceof ArrayType || $types[$i] instanceof IterableType) &&
						($types[$j] instanceof ArrayType || $types[$j] instanceof IterableType)
					) {
						$keyType = self::intersect($types[$i]->getKeyType(), $types[$j]->getKeyType());
						$itemType = self::intersect($types[$i]->getItemType(), $types[$j]->getItemType());
						if ($types[$i] instanceof IterableType && $types[$j] instanceof IterableType) {
							$types[$j] = new IterableType($keyType, $itemType);
						} else {
							$types[$j] = new ArrayType($keyType, $itemType);
						}
						array_splice($types, $i--, 1);
						$typesCount--;
						continue 2;
					}

					if ($types[$i] instanceof GenericClassStringType && $types[$j] instanceof GenericClassStringType) {
						$genericType = self::intersect($types[$i]->getGenericType(), $types[$j]->getGenericType());
						$types[$i] = new GenericClassStringType($genericType);
						array_splice($types, $j--, 1);
						$typesCount--;
						continue;
					}

					continue;
				}

				if ($isSuperTypeB->yes()) {
					array_splice($types, $i--, 1);
					$typesCount--;
					continue 2;
				}

				if ($isSuperTypeA->no()) {
					return new NeverType();
				}
			}
		}

		if ($typesCount === 1) {
			return $types[0];
		}

		return new IntersectionType($types);
	}

}
