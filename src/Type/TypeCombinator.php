<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateArrayType;
use PHPStan\Type\Generic\TemplateBenevolentUnionType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateUnionType;
use function array_key_exists;
use function array_key_first;
use function array_map;
use function array_merge;
use function array_slice;
use function array_splice;
use function array_values;
use function count;
use function get_class;
use function is_int;
use function sprintf;
use function usort;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * @api
 */
final class TypeCombinator
{

	public static function addNull(Type $type): Type
	{
		$nullType = new NullType();

		if ($nullType->isSuperTypeOf($type)->no()) {
			return self::union($type, $nullType);
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

		$removed = $fromType->tryRemove($typeToRemove);
		if ($removed !== null) {
			return $removed;
		}

		$fromFiniteTypes = $fromType->getFiniteTypes();
		if (count($fromFiniteTypes) > 0) {
			$finiteTypesToRemove = $typeToRemove->getFiniteTypes();
			if (count($finiteTypesToRemove) === 1) {
				$result = [];
				foreach ($fromFiniteTypes as $finiteType) {
					if ($finiteType->equals($finiteTypesToRemove[0])) {
						continue;
					}

					$result[] = $finiteType;
				}

				if (count($result) === count($fromFiniteTypes)) {
					return $fromType;
				}

				if (count($result) === 0) {
					return new NeverType();
				}

				if (count($result) === 1) {
					return $result[0];
				}

				return new UnionType($result);
			}
		}

		return $fromType;
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

		$alreadyNormalized = [];
		$alreadyNormalizedCounter = 0;

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
			$alreadyNormalized[$alreadyNormalizedCounter] = $typesInner;
			$alreadyNormalizedCounter++;
			array_splice($types, $i, 1, $typesInner);
			$typesCount += count($typesInner) - 1;
		}

		if ($typesCount === 1) {
			return $types[0];
		}

		$arrayTypes = [];
		$scalarTypes = [];
		$hasGenericScalarTypes = [];
		$enumCaseTypes = [];
		$integerRangeTypes = [];
		for ($i = 0; $i < $typesCount; $i++) {
			if ($types[$i]->isConstantScalarValue()->yes()) {
				$type = $types[$i];
				$scalarTypes[get_class($type)][$type->describe(VerbosityLevel::cache())] = $type;
				unset($types[$i]);
				continue;
			}

			if ($types[$i]->isBoolean()->yes()) {
				$hasGenericScalarTypes[ConstantBooleanType::class] = true;
			} elseif ($types[$i]->isFloat()->yes()) {
				$hasGenericScalarTypes[ConstantFloatType::class] = true;
			} elseif ($types[$i]->isInteger()->yes() && !$types[$i] instanceof IntegerRangeType) {
				$hasGenericScalarTypes[ConstantIntegerType::class] = true;
			} elseif ($types[$i]->isString()->yes() && $types[$i]->isClassString()->no() && TypeUtils::getAccessoryTypes($types[$i]) === []) {
				$hasGenericScalarTypes[ConstantStringType::class] = true;
			} else {
				$enumCases = $types[$i]->getEnumCases();
				if (count($enumCases) === 1) {
					$enumCaseTypes[$types[$i]->describe(VerbosityLevel::cache())] = $types[$i];

					unset($types[$i]);
					continue;
				}
			}

			if ($types[$i] instanceof IntegerRangeType) {
				$integerRangeTypes[] = $types[$i];
				unset($types[$i]);

				continue;
			}

			if (!$types[$i]->isArray()->yes()) {
				continue;
			}

			$arrayTypes[] = $types[$i];
			unset($types[$i]);
		}

		foreach ($scalarTypes as $classType => $scalarTypeItems) {
			$scalarTypes[$classType] = array_values($scalarTypeItems);
		}

		$enumCaseTypes = array_values($enumCaseTypes);
		usort(
			$integerRangeTypes,
			static fn (IntegerRangeType $a, IntegerRangeType $b): int => ($a->getMin() ?? PHP_INT_MIN) <=> ($b->getMin() ?? PHP_INT_MIN)
				?: ($a->getMax() ?? PHP_INT_MAX) <=> ($b->getMax() ?? PHP_INT_MAX),
		);
		$types = array_merge($types, $integerRangeTypes);
		$types = array_values($types);
		$typesCount = count($types);

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
		}

		$types = array_merge(
			$types,
			self::processArrayTypes($arrayTypes),
		);
		$typesCount = count($types);

		// transform A | A to A
		// transform A | never to A
		for ($i = 0; $i < $typesCount; $i++) {
			for ($j = $i + 1; $j < $typesCount; $j++) {
				if (self::isAlreadyNormalized($alreadyNormalized, $types[$i], $types[$j])) {
					continue;
				}
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

		$enumCasesCount = count($enumCaseTypes);
		for ($i = 0; $i < $typesCount; $i++) {
			for ($j = 0; $j < $enumCasesCount; $j++) {
				$compareResult = self::compareTypesInUnion($types[$i], $enumCaseTypes[$j]);
				if ($compareResult === null) {
					continue;
				}

				[$a, $b] = $compareResult;
				if ($a !== null) {
					$types[$i] = $a;
					array_splice($enumCaseTypes, $j--, 1);
					$enumCasesCount--;
					continue 1;
				}
				if ($b !== null) {
					$enumCaseTypes[$j] = $b;
					array_splice($types, $i--, 1);
					$typesCount--;
					continue 2;
				}
			}
		}

		foreach ($enumCaseTypes as $enumCaseType) {
			$types[] = $enumCaseType;
			$typesCount++;
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

				return new BenevolentUnionType($types, true);
			}
		}

		return new UnionType($types, true);
	}

	/**
	 * @param array<int, Type[]> $alreadyNormalized
	 */
	private static function isAlreadyNormalized(array $alreadyNormalized, Type $a, Type $b): bool
	{
		foreach ($alreadyNormalized as $normalizedTypes) {
			foreach ($normalizedTypes as $i => $normalizedType) {
				if ($normalizedType !== $a) {
					continue;
				}

				foreach ($normalizedTypes as $j => $anotherNormalizedType) {
					if ($i === $j) {
						continue;
					}
					if ($anotherNormalizedType === $b) {
						return true;
					}
				}
			}
		}

		return false;
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
		if ($a->isConstantArray()->yes() && $b->isConstantArray()->yes()) {
			return null;
		}

		// simplify string[] | int[] to (string|int)[]
		if ($a instanceof IterableType && $b instanceof IterableType) {
			return [
				new IterableType(
					self::union($a->getIterableKeyType(), $b->getIterableKeyType()),
					self::union($a->getIterableValueType(), $b->getIterableValueType()),
				),
				null,
			];
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
			return [null, self::intersect(
				new StringType(),
				...self::getAccessoryCaseStringTypes($b),
			)];
		}

		if (
			$b instanceof ConstantStringType
			&& $b->getValue() === ''
			&& ($a->describe(VerbosityLevel::value()) === 'non-empty-string'
				|| $a->describe(VerbosityLevel::value()) === 'non-falsy-string')
		) {
			return [self::intersect(
				new StringType(),
				...self::getAccessoryCaseStringTypes($a),
			), null];
		}

		if (
			$a instanceof ConstantStringType
			&& $a->getValue() === '0'
			&& $b->describe(VerbosityLevel::value()) === 'non-falsy-string'
		) {
			return [null, self::intersect(
				new StringType(),
				new AccessoryNonEmptyStringType(),
				...self::getAccessoryCaseStringTypes($b),
			)];
		}

		if (
			$b instanceof ConstantStringType
			&& $b->getValue() === '0'
			&& $a->describe(VerbosityLevel::value()) === 'non-falsy-string'
		) {
			return [self::intersect(
				new StringType(),
				new AccessoryNonEmptyStringType(),
				...self::getAccessoryCaseStringTypes($a),
			), null];
		}

		return null;
	}

	/**
	 * @return array<Type>
	 */
	private static function getAccessoryCaseStringTypes(Type $type): array
	{
		$accessory = [];
		if ($type->isLowercaseString()->yes()) {
			$accessory[] = new AccessoryLowercaseStringType();
		}
		if ($type->isUppercaseString()->yes()) {
			$accessory[] = new AccessoryUppercaseStringType();
		}

		return $accessory;
	}

	private static function unionWithSubtractedType(
		Type $type,
		?Type $subtractedType,
	): Type
	{
		if ($subtractedType === null) {
			return $type;
		}

		if ($subtractedType instanceof SubtractableType) {
			$withoutSubtracted = $subtractedType->getTypeWithoutSubtractedType();
			if ($withoutSubtracted->isSuperTypeOf($type)->yes()) {
				$subtractedSubtractedType = $subtractedType->getSubtractedType();
				if ($subtractedSubtractedType === null) {
					return new NeverType();
				}

				return self::intersect($type, $subtractedSubtractedType);
			}
		}

		if ($type instanceof SubtractableType) {
			$subtractedType = $type->getSubtractedType() === null
				? $subtractedType
				: self::union($type->getSubtractedType(), $subtractedType);

			$subtractedType = self::intersect(
				$type->getTypeWithoutSubtractedType(),
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
		if ($a->getSubtractedType() === null || $b instanceof NeverType) {
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
		} else {
			$isBAlreadySubtracted = $a->getSubtractedType()->isSuperTypeOf($b);

			if ($isBAlreadySubtracted->no()) {
				return $a;
			} elseif ($isBAlreadySubtracted->yes()) {
				$subtractedType = self::remove($a->getSubtractedType(), $b);

				if (
					$subtractedType instanceof NeverType
					|| !$subtractedType->isSuperTypeOf($b)->no()
				) {
					$subtractedType = null;
				}

				return $a->changeSubtractedType($subtractedType);
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
				$subtractedType = new MixedType(subtractedType: $b);
			}
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
	 * @param Type[] $arrayTypes
	 * @return Type[]
	 */
	private static function processArrayAccessoryTypes(array $arrayTypes): array
	{
		$isIterableAtLeastOnce = [];
		$accessoryTypes = [];
		foreach ($arrayTypes as $i => $arrayType) {
			$isIterableAtLeastOnce[] = $arrayType->isIterableAtLeastOnce();

			if ($arrayType instanceof IntersectionType) {
				foreach ($arrayType->getTypes() as $innerType) {
					if ($innerType instanceof TemplateType) {
						break;
					}
					if (!($innerType instanceof AccessoryType) && !($innerType instanceof CallableType)) {
						continue;
					}
					if ($innerType instanceof HasOffsetType) {
						$offset = $innerType->getOffsetType();
						if ($offset instanceof ConstantStringType || $offset instanceof ConstantIntegerType) {
							$innerType = new HasOffsetValueType($offset, $arrayType->getIterableValueType());
						}
					}
					if ($innerType instanceof HasOffsetValueType) {
						$accessoryTypes[sprintf('hasOffsetValue(%s)', $innerType->getOffsetType()->describe(VerbosityLevel::cache()))][$i] = $innerType;
						continue;
					}

					$accessoryTypes[$innerType->describe(VerbosityLevel::cache())][$i] = $innerType;
				}
			}

			if (!$arrayType->isConstantArray()->yes()) {
				continue;
			}
			$constantArrays = $arrayType->getConstantArrays();

			foreach ($constantArrays as $constantArray) {
				if ($constantArray->isList()->yes()) {
					$list = new AccessoryArrayListType();
					$accessoryTypes[$list->describe(VerbosityLevel::cache())][$i] = $list;
				}

				if (!$constantArray->isIterableAtLeastOnce()->yes()) {
					continue;
				}

				$nonEmpty = new NonEmptyArrayType();
				$accessoryTypes[$nonEmpty->describe(VerbosityLevel::cache())][$i] = $nonEmpty;
			}
		}

		$commonAccessoryTypes = [];
		$arrayTypeCount = count($arrayTypes);
		foreach ($accessoryTypes as $accessoryType) {
			if (count($accessoryType) !== $arrayTypeCount) {
				$firstKey = array_key_first($accessoryType);
				if ($accessoryType[$firstKey] instanceof OversizedArrayType) {
					$commonAccessoryTypes[] = $accessoryType[$firstKey];
				}
				continue;
			}

			if ($accessoryType[0] instanceof HasOffsetValueType) {
				$commonAccessoryTypes[] = self::union(...$accessoryType);
				continue;
			}

			$commonAccessoryTypes[] = $accessoryType[0];
		}

		if (TrinaryLogic::createYes()->and(...$isIterableAtLeastOnce)->yes()) {
			$commonAccessoryTypes[] = new NonEmptyArrayType();
		}

		return $commonAccessoryTypes;
	}

	/**
	 * @param list<Type> $arrayTypes
	 * @return Type[]
	 */
	private static function processArrayTypes(array $arrayTypes): array
	{
		if ($arrayTypes === []) {
			return [];
		}

		$accessoryTypes = self::processArrayAccessoryTypes($arrayTypes);

		if (count($arrayTypes) === 1) {
			return [
				self::intersect(...$arrayTypes, ...$accessoryTypes),
			];
		}

		$keyTypesForGeneralArray = [];
		$valueTypesForGeneralArray = [];
		$generalArrayOccurred = false;
		$constantKeyTypesNumbered = [];
		$filledArrays = 0;
		$overflowed = false;

		/** @var int|float $nextConstantKeyTypeIndex */
		$nextConstantKeyTypeIndex = 1;
		$constantArraysMap = array_map(
			static fn (Type $t) => $t->getConstantArrays(),
			$arrayTypes,
		);

		foreach ($arrayTypes as $arrayIdx => $arrayType) {
			$constantArrays = $constantArraysMap[$arrayIdx];
			$isConstantArray = $constantArrays !== [];
			if (!$isConstantArray || !$arrayType->isIterableAtLeastOnce()->no()) {
				$filledArrays++;
			}

			if ($generalArrayOccurred || !$isConstantArray) {
				foreach ($arrayType->getArrays() as $type) {
					$keyTypesForGeneralArray[] = $type->getIterableKeyType();
					$valueTypesForGeneralArray[] = $type->getItemType();
					$generalArrayOccurred = true;
				}
				continue;
			}

			$constantArrays = $arrayType->getConstantArrays();
			foreach ($constantArrays as $constantArray) {
				foreach ($constantArray->getKeyTypes() as $i => $keyType) {
					$keyTypesForGeneralArray[] = $keyType;
					$valueTypesForGeneralArray[] = $constantArray->getValueTypes()[$i];

					$keyTypeValue = $keyType->getValue();
					if (array_key_exists($keyTypeValue, $constantKeyTypesNumbered)) {
						continue;
					}

					$constantKeyTypesNumbered[$keyTypeValue] = $nextConstantKeyTypeIndex;
					$nextConstantKeyTypeIndex *= 2;
					if (!is_int($nextConstantKeyTypeIndex)) {
						$generalArrayOccurred = true;
						$overflowed = true;
						continue 2;
					}
				}
			}
		}

		if ($generalArrayOccurred && (!$overflowed || $filledArrays > 1)) {
			$reducedArrayTypes = self::reduceArrays($arrayTypes, false);
			if (count($reducedArrayTypes) === 1) {
				return [self::intersect($reducedArrayTypes[0], ...$accessoryTypes)];
			}
			$scopes = [];
			$useTemplateArray = true;
			foreach ($arrayTypes as $arrayType) {
				if (!$arrayType instanceof TemplateArrayType) {
					$useTemplateArray = false;
					break;
				}

				$scopes[$arrayType->getScope()->describe()] = $arrayType;
			}

			$arrayType = new ArrayType(
				self::union(...$keyTypesForGeneralArray),
				self::union(...self::optimizeConstantArrays($valueTypesForGeneralArray)),
			);

			if ($useTemplateArray && count($scopes) === 1) {
				$templateArray = array_values($scopes)[0];
				$arrayType = new TemplateArrayType(
					$templateArray->getScope(),
					$templateArray->getStrategy(),
					$templateArray->getVariance(),
					$templateArray->getName(),
					$arrayType,
					$templateArray->getDefault(),
				);
			}

			return [
				self::intersect($arrayType, ...$accessoryTypes),
			];
		}

		$reducedArrayTypes = self::reduceArrays($arrayTypes, true);

		return array_map(
			static fn (Type $arrayType) => self::intersect($arrayType, ...$accessoryTypes),
			self::optimizeConstantArrays($reducedArrayTypes),
		);
	}

	/**
	 * @param Type[] $types
	 * @return Type[]
	 */
	private static function optimizeConstantArrays(array $types): array
	{
		$constantArrayValuesCount = self::countConstantArrayValueTypes($types);

		if ($constantArrayValuesCount <= ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
			return $types;
		}

		$results = [];
		$eachIsOversized = true;
		foreach ($types as $type) {
			$isOversized = false;
			$result = TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$isOversized): Type {
				if (!$type instanceof ConstantArrayType) {
					return $traverse($type);
				}

				if ($type->isIterableAtLeastOnce()->no()) {
					return $type;
				}

				$isOversized = true;

				$isList = true;
				$valueTypes = [];
				$keyTypes = [];
				$nextAutoIndex = 0;
				foreach ($type->getKeyTypes() as $i => $innerKeyType) {
					if (!$innerKeyType instanceof ConstantIntegerType) {
						$isList = false;
					} elseif ($innerKeyType->getValue() !== $nextAutoIndex) {
						$isList = false;
						$nextAutoIndex = $innerKeyType->getValue() + 1;
					} else {
						$nextAutoIndex++;
					}

					$generalizedKeyType = $innerKeyType->generalize(GeneralizePrecision::moreSpecific());
					$keyTypes[$generalizedKeyType->describe(VerbosityLevel::precise())] = $generalizedKeyType;

					$innerValueType = $type->getValueTypes()[$i];
					$generalizedValueType = TypeTraverser::map($innerValueType, static function (Type $type) use ($traverse): Type {
						if ($type instanceof ArrayType || $type instanceof ConstantArrayType) {
							return TypeCombinator::intersect($type, new OversizedArrayType());
						}

						if ($type instanceof ConstantScalarType) {
							return $type->generalize(GeneralizePrecision::moreSpecific());
						}

						return $traverse($type);
					});
					$valueTypes[$generalizedValueType->describe(VerbosityLevel::precise())] = $generalizedValueType;
				}

				$keyType = TypeCombinator::union(...array_values($keyTypes));
				$valueType = TypeCombinator::union(...array_values($valueTypes));

				$arrayType = new ArrayType($keyType, $valueType);
				if ($isList) {
					$arrayType = TypeCombinator::intersect($arrayType, new AccessoryArrayListType());
				}

				return TypeCombinator::intersect($arrayType, new NonEmptyArrayType(), new OversizedArrayType());
			});

			if (!$isOversized) {
				$eachIsOversized = false;
			}

			$results[] = $result;
		}

		if ($eachIsOversized) {
			$eachIsList = true;
			$keyTypes = [];
			$valueTypes = [];
			foreach ($results as $result) {
				$keyTypes[] = $result->getIterableKeyType();
				$valueTypes[] = $result->getIterableValueType();
				if ($result->isList()->yes()) {
					continue;
				}
				$eachIsList = false;
			}

			$keyType = self::union(...$keyTypes);
			$valueType = self::union(...$valueTypes);

			if ($valueType instanceof UnionType && count($valueType->getTypes()) > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
				$valueType = $valueType->generalize(GeneralizePrecision::lessSpecific());
			}

			$arrayType = new ArrayType($keyType, $valueType);
			if ($eachIsList) {
				$arrayType = self::intersect($arrayType, new AccessoryArrayListType());
			}

			return [self::intersect($arrayType, new NonEmptyArrayType(), new OversizedArrayType())];
		}

		return $results;
	}

	/**
	 * @param Type[] $types
	 */
	public static function countConstantArrayValueTypes(array $types): int
	{
		$constantArrayValuesCount = 0;
		foreach ($types as $type) {
			TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$constantArrayValuesCount): Type {
				if ($type instanceof ConstantArrayType) {
					$constantArrayValuesCount += count($type->getValueTypes());
				}

				return $traverse($type);
			});
		}
		return $constantArrayValuesCount;
	}

	/**
	 * @param list<Type> $constantArrays
	 * @return list<Type>
	 */
	private static function reduceArrays(array $constantArrays, bool $preserveTaggedUnions): array
	{
		$newArrays = [];
		$arraysToProcess = [];
		$emptyArray = null;
		foreach ($constantArrays as $constantArray) {
			if (!$constantArray->isConstantArray()->yes()) {
				// This is an optimization for current use-case of $preserveTaggedUnions=false, where we need
				// one constant array as a result, or we generalize the $constantArrays.
				if (!$preserveTaggedUnions) {
					return $constantArrays;
				}
				$newArrays[] = $constantArray;
				continue;
			}

			if ($constantArray->isIterableAtLeastOnce()->no()) {
				$emptyArray = $constantArray;
				continue;
			}

			$arraysToProcess = array_merge($arraysToProcess, $constantArray->getConstantArrays());
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
					$eligibleCombinations[$arrays[$i]][$arrays[$j]] ??= 0;
					$eligibleCombinations[$arrays[$i]][$arrays[$j]]++;
				}
			}
		}

		foreach ($eligibleCombinations as $i => $other) {
			if (!array_key_exists($i, $arraysToProcess)) {
				continue;
			}

			foreach ($other as $j => $overlappingKeysCount) {
				if (!array_key_exists($j, $arraysToProcess)) {
					continue;
				}

				if (
					$preserveTaggedUnions
					&& $overlappingKeysCount === count($arraysToProcess[$i]->getKeyTypes())
					&& $arraysToProcess[$j]->isKeysSupersetOf($arraysToProcess[$i])
				) {
					$arraysToProcess[$j] = $arraysToProcess[$j]->mergeWith($arraysToProcess[$i]);
					unset($arraysToProcess[$i]);
					continue 2;
				}

				if (
					$preserveTaggedUnions
					&& $overlappingKeysCount === count($arraysToProcess[$j]->getKeyTypes())
					&& $arraysToProcess[$i]->isKeysSupersetOf($arraysToProcess[$j])
				) {
					$arraysToProcess[$i] = $arraysToProcess[$i]->mergeWith($arraysToProcess[$j]);
					unset($arraysToProcess[$j]);
					continue 1;
				}

				if (
					!$preserveTaggedUnions
					// both arrays have same keys
					&& $overlappingKeysCount === count($arraysToProcess[$i]->getKeyTypes())
					&& $overlappingKeysCount === count($arraysToProcess[$j]->getKeyTypes())
				) {
					$arraysToProcess[$j] = $arraysToProcess[$j]->mergeWith($arraysToProcess[$i]);
					unset($arraysToProcess[$i]);
					continue 2;
				}
			}
		}

		return array_merge($newArrays, $arraysToProcess);
	}

	public static function intersect(Type ...$types): Type
	{
		$types = array_values($types);

		$typesCount = count($types);
		if ($typesCount === 0) {
			return new NeverType();
		}
		if ($typesCount === 1) {
			return $types[0];
		}

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
					$type->getDefault(),
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
			$types = $newTypes;
			$typesCount = count($types);
		}

		usort($types, static function (Type $a, Type $b): int {
			// move subtractables with subtracts before those without to avoid losing them in the union logic
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

					if (
						$types[$i] instanceof ConstantArrayType
						&& count($types[$i]->getKeyTypes()) === 1
						&& $types[$i]->isOptionalKey(0)
						&& $types[$j] instanceof NonEmptyArrayType
					) {
						$types[$i] = $types[$i]->makeOffsetRequired($types[$i]->getKeyTypes()[0]);
						array_splice($types, $j--, 1);
						$typesCount--;
						continue;
					}

					if (
						$types[$j] instanceof ConstantArrayType
						&& count($types[$j]->getKeyTypes()) === 1
						&& $types[$j]->isOptionalKey(0)
						&& $types[$i] instanceof NonEmptyArrayType
					) {
						$types[$j] = $types[$j]->makeOffsetRequired($types[$j]->getKeyTypes()[0]);
						array_splice($types, $i--, 1);
						$typesCount--;
						continue 2;
					}

					if ($types[$i] instanceof ConstantArrayType && $types[$j] instanceof HasOffsetValueType) {
						$offsetType = $types[$j]->getOffsetType();
						$valueType = $types[$j]->getValueType();
						$newValueType = self::intersect($types[$i]->getOffsetValueType($offsetType), $valueType);
						if ($newValueType instanceof NeverType) {
							return $newValueType;
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
							return $newValueType;
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

					if ($types[$i] instanceof ObjectShapeType && $types[$j] instanceof HasPropertyType) {
						$types[$i] = $types[$i]->makePropertyRequired($types[$j]->getPropertyName());
						array_splice($types, $j--, 1);
						$typesCount--;
						continue;
					}

					if ($types[$j] instanceof ObjectShapeType && $types[$i] instanceof HasPropertyType) {
						$types[$j] = $types[$j]->makePropertyRequired($types[$i]->getPropertyName());
						array_splice($types, $i--, 1);
						$typesCount--;
						continue 2;
					}

					if ($types[$i] instanceof ConstantArrayType && ($types[$j] instanceof ArrayType || $types[$j] instanceof ConstantArrayType)) {
						$newArray = ConstantArrayTypeBuilder::createEmpty();
						$valueTypes = $types[$i]->getValueTypes();
						foreach ($types[$i]->getKeyTypes() as $k => $keyType) {
							$newArray->setOffsetValueType(
								self::intersect($keyType, $types[$j]->getIterableKeyType()),
								self::intersect($valueTypes[$k], $types[$j]->getIterableValueType()),
								$types[$i]->isOptionalKey($k) && !$types[$j]->hasOffsetValueType($keyType)->yes(),
							);
						}
						$types[$i] = $newArray->getArray();
						array_splice($types, $j--, 1);
						$typesCount--;
						continue 2;
					}

					if ($types[$j] instanceof ConstantArrayType && ($types[$i] instanceof ArrayType || $types[$i] instanceof ConstantArrayType)) {
						$newArray = ConstantArrayTypeBuilder::createEmpty();
						$valueTypes = $types[$j]->getValueTypes();
						foreach ($types[$j]->getKeyTypes() as $k => $keyType) {
							$newArray->setOffsetValueType(
								self::intersect($keyType, $types[$i]->getIterableKeyType()),
								self::intersect($valueTypes[$k], $types[$i]->getIterableValueType()),
								$types[$j]->isOptionalKey($k) && !$types[$i]->hasOffsetValueType($keyType)->yes(),
							);
						}
						$types[$j] = $newArray->getArray();
						array_splice($types, $i--, 1);
						$typesCount--;
						continue 2;
					}

					if (
						($types[$i] instanceof ArrayType || $types[$i] instanceof ConstantArrayType || $types[$i] instanceof IterableType) &&
						($types[$j] instanceof ArrayType || $types[$j] instanceof ConstantArrayType || $types[$j] instanceof IterableType)
					) {
						$keyType = self::intersect($types[$i]->getIterableKeyType(), $types[$j]->getKeyType());
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

					if (
						$types[$i] instanceof ArrayType
						&& get_class($types[$i]) === ArrayType::class
						&& $types[$j] instanceof AccessoryArrayListType
						&& !$types[$j]->getIterableKeyType()->isSuperTypeOf($types[$i]->getIterableKeyType())->yes()
					) {
						$keyType = self::intersect($types[$i]->getIterableKeyType(), $types[$j]->getIterableKeyType());
						if ($keyType instanceof NeverType) {
							return $keyType;
						}
						$types[$i] = new ArrayType($keyType, $types[$i]->getItemType());
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

	public static function removeFalsey(Type $type): Type
	{
		return self::remove($type, StaticTypeFactory::falsey());
	}

	public static function removeTruthy(Type $type): Type
	{
		return self::remove($type, StaticTypeFactory::truthy());
	}

}
