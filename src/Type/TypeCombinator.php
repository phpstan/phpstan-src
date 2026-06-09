<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType;
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
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateUnionType;
use function array_fill;
use function array_filter;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_merge;
use function array_slice;
use function array_splice;
use function array_values;
use function count;
use function get_class;
use function implode;
use function in_array;
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
			if (count($finiteTypesToRemove) > 0) {
				$result = [];
				foreach ($fromFiniteTypes as $finiteType) {
					foreach ($finiteTypesToRemove as $finiteTypeToRemove) {
						if ($finiteType->equals($finiteTypeToRemove)) {
							continue 2;
						}
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

		// Fast path for single non-union type
		if ($typesCount === 1) {
			$singleType = $types[0];
			if (!$singleType instanceof UnionType && !$singleType->isArray()->yes()) {
				return $singleType;
			}
		}

		// Fast path for common 2-type cases
		if ($typesCount === 2) {
			$a = $types[0];
			$b = $types[1];

			// union(never, X) = X and union(X, never) = X
			if ($a instanceof NeverType && !$a->isExplicit()) {
				return $b;
			}
			if ($b instanceof NeverType && !$b->isExplicit()) {
				return $a;
			}

			// union(mixed, X) = mixed (non-explicit, non-template, no subtracted)
			if ($a instanceof MixedType && !$a->isExplicitMixed() && !$a instanceof TemplateMixedType && $a->getSubtractedType() === null) {
				return $a;
			}
			if ($b instanceof MixedType && !$b->isExplicitMixed() && !$b instanceof TemplateMixedType && $b->getSubtractedType() === null) {
				return $b;
			}

			// union(X, X) = X (same object identity)
			if ($a === $b) {
				return $a;
			}
		}

		$alreadyNormalized = [];
		$alreadyNormalizedCounter = 0;

		$benevolentTypes = [];
		$benevolentUnionObject = null;
		$neverCount = 0;
		// transform A | (B | C) to A | B | C
		for ($i = 0; $i < $typesCount; $i++) {
			if (
				$types[$i] instanceof MixedType
				&& !$types[$i]->isExplicitMixed()
				&& !$types[$i] instanceof TemplateMixedType
				&& $types[$i]->getSubtractedType() === null
			) {
				return $types[$i];
			}
			if ($types[$i] instanceof NeverType && !$types[$i]->isExplicit()) {
				$neverCount++;
				continue;
			}
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

		// Bulk-remove implicit NeverTypes (skipped during the loop above)
		if ($neverCount > 0) {
			if ($neverCount === $typesCount) {
				return new NeverType();
			}

			$filtered = [];
			for ($i = 0; $i < $typesCount; $i++) {
				if ($types[$i] instanceof NeverType && !$types[$i]->isExplicit()) {
					continue;
				}
				$filtered[] = $types[$i];
			}
			$types = $filtered;
			$typesCount = count($types);

			if ($typesCount === 0) {
				return new NeverType();
			}
			if ($typesCount === 1 && !$types[0]->isArray()->yes()) {
				return $types[0];
			}
			if ($typesCount === 2) {
				return self::union($types[0], $types[1]);
			}
		}

		if ($typesCount === 0) {
			return new NeverType();
		}

		if ($typesCount === 1 && !$types[0]->isArray()->yes()) {
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
				$enumCase = $types[$i]->getEnumCaseObject();
				if ($enumCase !== null) {
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

			$scalarTypeItems = array_values($scalarTypeItems);
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
						array_splice($scalarTypeItems, $j, 1);
						$scalarTypeItemsCount--;
						$j = -1;
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
					return $benevolentUnionObject->withTypes(array_values($types));
				}

				return new BenevolentUnionType(array_values($types), true);
			}
		}

		return new UnionType(array_values($types), true);
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
		if ($a instanceof IntersectionType && $b instanceof IntersectionType) {
			$merged = self::mergeIntersectionsForUnion($a, $b);
			if ($merged !== null) {
				return [$merged, null];
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
		) {
			if ($a->getValue() === '') {
				$description = $b->describe(VerbosityLevel::value());
				if (in_array($description, ['non-empty-string', 'non-falsy-string'], true)) {
					return [null, self::intersect(
						new StringType(),
						...self::getAccessoryCaseStringTypes($b),
					)];
				}
			}

			if ($a->getValue() === '0') {
				$description = $b->describe(VerbosityLevel::value());
				if ($description === 'non-falsy-string') {
					return [null, new IntersectionType([
						new StringType(),
						new AccessoryNonEmptyStringType(),
						...self::getAccessoryCaseStringTypes($b),
					])];
				}
			}
		}

		if (
			$b instanceof ConstantStringType
		) {
			if ($b->getValue() === '') {
				$description = $a->describe(VerbosityLevel::value());
				if (in_array($description, ['non-empty-string', 'non-falsy-string'], true)) {
					return [self::intersect(
						new StringType(),
						...self::getAccessoryCaseStringTypes($a),
					), null];
				}
			}

			if ($b->getValue() === '0') {
				$description = $a->describe(VerbosityLevel::value());
				if ($description === 'non-falsy-string') {
					return [new IntersectionType([
						new StringType(),
						new AccessoryNonEmptyStringType(),
						...self::getAccessoryCaseStringTypes($a),
					]), null];
				}
			}
		}

		// numeric-string | non-decimal-int-string → string (preserving common accessories)
		// Works because decimal-int-string ⊂ numeric-string, so together they cover all strings
		if ($a->isString()->yes() && $b->isString()->yes()) {
			$decimalIntString = new IntersectionType([new StringType(), new AccessoryDecimalIntegerStringType()]);
			if ($b->isDecimalIntegerString()->no()) {
				$bBase = self::removeDecimalIntStringAccessory($b);
				if ($bBase->isSuperTypeOf($a)->yes() && $a->isSuperTypeOf($decimalIntString)->yes()) {
					return [null, $bBase];
				}
			}
			if ($a->isDecimalIntegerString()->no()) {
				$aBase = self::removeDecimalIntStringAccessory($a);
				if ($aBase->isSuperTypeOf($b)->yes() && $b->isSuperTypeOf($decimalIntString)->yes()) {
					return [$aBase, null];
				}
			}
		}

		return null;
	}

	/**
	 * @return list<Type>
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

	private static function removeDecimalIntStringAccessory(Type $type): Type
	{
		if (!$type instanceof IntersectionType) {
			return $type;
		}

		return self::intersect(...array_filter(
			$type->getTypes(),
			static fn (Type $t): bool => !$t instanceof AccessoryDecimalIntegerStringType,
		));
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
	 * @return list<Type>
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
						$innerType = new HasOffsetValueType($innerType->getOffsetType(), $arrayType->getIterableValueType());
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

		foreach ($arrayTypes as $arrayType) {
			$constantArrays = $arrayType->getConstantArrays();
			$isConstantArray = $constantArrays !== [];
			if (!$isConstantArray || !$arrayType->isIterableAtLeastOnce()->no()) {
				$filledArrays++;
			}

			if (!$isConstantArray) {
				foreach ($arrayType->getArrays() as $type) {
					$keyTypesForGeneralArray[] = $type->getIterableKeyType();
					$valueTypesForGeneralArray[] = $type->getItemType();
					$generalArrayOccurred = true;
				}
				continue;
			}

			foreach ($constantArrays as $constantArray) {
				$valueTypes = $constantArray->getValueTypes();
				foreach ($constantArray->getKeyTypes() as $i => $keyType) {
					$valueTypesForGeneralArray[] = $valueTypes[$i];

					$keyTypeValue = $keyType->getValue();
					if (array_key_exists($keyTypeValue, $constantKeyTypesNumbered)) {
						continue;
					}
					$keyTypesForGeneralArray[] = $keyType;

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

			$templateArrayType = null;
			foreach ($arrayTypes as $arrayType) {
				if (!$arrayType instanceof TemplateArrayType) {
					$templateArrayType = null;
					break;
				}

				if ($templateArrayType !== null) {
					continue;
				}

				$templateArrayType = $arrayType;
			}

			$arrayType = new ArrayType(
				self::union(...$keyTypesForGeneralArray),
				self::union(...self::optimizeConstantArrays($valueTypesForGeneralArray)),
			);

			if ($templateArrayType !== null) {
				$arrayType = new TemplateArrayType(
					$templateArrayType->getScope(),
					$templateArrayType->getStrategy(),
					$templateArrayType->getVariance(),
					$templateArrayType->getName(),
					$arrayType,
					$templateArrayType->getDefault(),
				);
			}

			return [
				self::intersect($arrayType, ...$accessoryTypes),
			];
		}

		$reducedArrayTypes = self::optimizeConstantArrays(self::reduceArrays($arrayTypes, true));
		foreach ($reducedArrayTypes as $idx => $reducedArray) {
			$applied = $accessoryTypes;
			if ($reducedArray->isIterableAtLeastOnce()->no()) {
				// Empty arrays cannot satisfy non-empty / oversized constraints —
				// applying those accessories would produce a contradictory intersection
				// (e.g. `array{}&oversized-array`) that rejects the very value it
				// represents, breaking the super-type contract of the union.
				$applied = array_values(array_filter(
					$applied,
					static fn (Type $t): bool => !($t instanceof OversizedArrayType) && !($t instanceof NonEmptyArrayType),
				));
			}
			$reducedArrayTypes[$idx] = self::intersect($reducedArray, ...$applied);
		}
		return $reducedArrayTypes;
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

		// Stage 1: collapse same-key-set ConstantArrayType variants per-position
		// before the (lossy) generalization below kicks in. Variants with the
		// same key signature mergeWith losslessly into a single shape whose
		// values at each position are the union of the variants' values, which
		// drops the count while keeping the per-position structure. Without
		// this, a list of N similarly-shaped records (e.g. bug-7963) hits the
		// limit and the generalization decomposes every nested constant array
		// into a flat `non-empty-list<unionOfAllPositionValues>`, losing the
		// shape entirely.
		$signatureGroups = [];
		$nonConstantTypes = [];
		foreach ($types as $idx => $type) {
			if (!$type instanceof ConstantArrayType) {
				$nonConstantTypes[$idx] = $type;
				continue;
			}
			$signatureParts = [];
			$signatureParts[] = $type->isList()->yes() ? 'L' : 'A';
			foreach ($type->getKeyTypes() as $i => $keyType) {
				$signatureParts[] = ($type->isOptionalKey($i) ? '?' : '!') . ($keyType instanceof ConstantIntegerType ? 'i' : 's') . $keyType->getValue();
			}
			$signatureGroups[implode(',', $signatureParts)][] = $type;
		}
		if ($signatureGroups !== []) {
			$collapsed = $nonConstantTypes;
			$anyMerged = false;
			foreach ($signatureGroups as $group) {
				if (count($group) === 1) {
					$collapsed[] = $group[0];
					continue;
				}
				$merged = $group[0];
				for ($i = 1, $count = count($group); $i < $count; $i++) {
					$merged = $merged->mergeWith($group[$i]);
				}
				$collapsed[] = $merged;
				$anyMerged = true;
			}
			if ($anyMerged) {
				$types = array_values($collapsed);
				$constantArrayValuesCount = self::countConstantArrayValueTypes($types);
				if ($constantArrayValuesCount <= ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
					return $types;
				}
			}
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
				$innerValueTypes = $type->getValueTypes();
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

					// Inner traversal of the value position. Two subtleties, both
					// of which produced types that failed to be super-types of
					// their contributors:
					// - Empty constant arrays must be left alone; wrapping them
					//   builds a contradictory `array{}&oversized-array`.
					// - Fall through via `$innerTraverse`, not the outer
					//   `$traverse`. The outer callback fully generalizes a
					//   sealed `ConstantArrayType` into `array<intKey, V>&...`,
					//   which is correct at the top level but wrong inside a
					//   value position: it would treat a sealed `array{a: 1}`
					//   reached via `array{}|array{a: 1}` differently from one
					//   reached directly, leaving `processArrayTypes` with a
					//   mix of shapes it cannot unify cleanly.
					$generalizedValueType = TypeTraverser::map($innerValueTypes[$i], static function (Type $type, callable $innerTraverse): Type {
						if ($type instanceof ConstantArrayType && $type->isIterableAtLeastOnce()->no()) {
							return $type;
						}

						if ($type instanceof ArrayType || $type instanceof ConstantArrayType) {
							return new IntersectionType([$type, new OversizedArrayType()]);
						}

						if ($type instanceof ConstantScalarType) {
							return $type->generalize(GeneralizePrecision::moreSpecific());
						}

						return $innerTraverse($type);
					});
					$valueTypes[$generalizedValueType->describe(VerbosityLevel::precise())] = $generalizedValueType;
				}

				$keyType = TypeCombinator::union(...array_values($keyTypes));
				$valueType = TypeCombinator::union(...array_values($valueTypes));

				$accessories = [];
				if ($isList) {
					$accessories[] = new AccessoryArrayListType();
				}
				$accessories[] = new NonEmptyArrayType();
				$accessories[] = new OversizedArrayType();

				return self::intersect(new ArrayType($keyType, $valueType), ...$accessories);
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

			$accessories = [];
			if ($eachIsList) {
				$accessories[] = new AccessoryArrayListType();
			}
			$accessories[] = new NonEmptyArrayType();
			$accessories[] = new OversizedArrayType();

			return [self::intersect(new ArrayType($keyType, $valueType), ...$accessories)];
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
			if ($preserveTaggedUnions && $emptyArray instanceof ConstantArrayType) {
				// Let the empty array participate in merging — the passes below will absorb
				// it into any array that already accepts [] (all-optional keys, compatible
				// unsealed extras). If no such array exists, it remains as-is in the result.
				$arraysToProcess[] = $emptyArray;
			} else {
				$newArrays[] = $emptyArray;
			}
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

				// Merge two single-key arrays sharing the same key when their value
				// types union into a single type (not a UnionType). This is lossless
				// and prevents exponential union growth when narrowing nested
				// ArrayDimFetch expressions on a ConstantArrayType parent (see
				// phpstan/phpstan#14462).
				if (
					$preserveTaggedUnions
					&& $overlappingKeysCount === 1
					&& count($arraysToProcess[$i]->getKeyTypes()) === 1
					&& count($arraysToProcess[$j]->getKeyTypes()) === 1
				) {
					$iValueType = $arraysToProcess[$i]->getValueTypes()[0];
					$jValueType = $arraysToProcess[$j]->getValueTypes()[0];
					$unionValueType = self::union($iValueType, $jValueType);
					if (!$unionValueType instanceof UnionType) {
						$arraysToProcess[$j] = $arraysToProcess[$j]->mergeWith($arraysToProcess[$i]);
						unset($arraysToProcess[$i]);
						continue 2;
					}
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

		// Second pass: merge pairs that the eligibleCombinations loop above couldn't touch.
		// That loop only considers pairs sharing at least one known key, so it never fires
		// for e.g. `array{}` ∪ `array{a?: 1}` (disjoint, one empty) or for two
		// unsealed-extras arrays with disjoint required keys. Both collapse losslessly if
		// one side's extras or optional-key shape can absorb the other side's content.
		//
		// Performance: two sealed, non-empty, no-extras arrays with disjoint keys cannot
		// merge losslessly (legacyIsKeysSupersetOf returns false immediately on the first
		// missing key). Skip those pairs via a candidate flag to avoid an O(n²) scan that
		// dominated analyse time on files accumulating many sealed ConstantArrayType
		// variants (bug-7581 / bug-8146a). A pair is worth checking only if at least one
		// side is (a) empty, or (b) has real unsealed extras, or (c) has optional keys —
		// the last case covers the narrowing shape used by e.g. array_key_exists checks
		// over large optional-key shapes (bug-14032).
		$indices = array_keys($arraysToProcess);
		$indicesCount = count($indices);
		if ($indicesCount > 1) {
			$candidateFlags = [];
			foreach ($indices as $idx) {
				$arr = $arraysToProcess[$idx];
				$unsealed = $arr->getUnsealedTypes();
				if ($unsealed === null) {
					$candidateFlags[$idx] = false;
					continue;
				}
				[$unsealedKey] = $unsealed;
				$hasRealExtras = !($unsealedKey instanceof NeverType && $unsealedKey->isExplicit());
				if ($hasRealExtras) {
					$candidateFlags[$idx] = true;
					continue;
				}
				$keyTypesCount = count($arr->getKeyTypes());
				if ($keyTypesCount === 0) {
					$candidateFlags[$idx] = true;
					continue;
				}
				$hasOptional = count($arr->getOptionalKeys()) > 0;
				$candidateFlags[$idx] = $hasOptional;
			}

			for ($ii = 0; $ii < $indicesCount - 1; $ii++) {
				$i = $indices[$ii];
				if (!array_key_exists($i, $arraysToProcess)) {
					continue;
				}
				if ($arraysToProcess[$i]->getUnsealedTypes() === null) {
					continue;
				}
				for ($jj = $ii + 1; $jj < $indicesCount; $jj++) {
					$j = $indices[$jj];
					if (!array_key_exists($j, $arraysToProcess)) {
						continue;
					}
					if (!$candidateFlags[$i] && !$candidateFlags[$j]) {
						continue;
					}
					if ($arraysToProcess[$j]->getUnsealedTypes() === null) {
						continue;
					}
					if ($arraysToProcess[$j]->isKeysSupersetOf($arraysToProcess[$i])) {
						$arraysToProcess[$j] = $arraysToProcess[$j]->mergeWith($arraysToProcess[$i]);
						unset($arraysToProcess[$i]);
						continue 2;
					}
					if (!$arraysToProcess[$i]->isKeysSupersetOf($arraysToProcess[$j])) {
						continue;
					}

					$arraysToProcess[$i] = $arraysToProcess[$i]->mergeWith($arraysToProcess[$j]);
					unset($arraysToProcess[$j]);
				}
			}
		}

		// Final pass: if merging left us with a ConstantArrayType that has no known keys
		// but has real unsealed extras, collapse it to a plain ArrayType (mirrors the same
		// logic in ConstantArrayTypeBuilder::getArray — but applies to results produced by
		// ConstantArrayType::mergeWith, which doesn't go through the builder).
		foreach ($arraysToProcess as $idx => $arr) {
			if (count($arr->getKeyTypes()) !== 0) {
				continue;
			}
			$unsealed = $arr->getUnsealedTypes();
			if ($unsealed === null) {
				continue;
			}
			[$unsealedKey, $unsealedValue] = $unsealed;
			if ($unsealedKey instanceof NeverType && $unsealedKey->isExplicit()) {
				continue;
			}
			$newArrays[] = new ArrayType($unsealedKey, $unsealedValue);
			unset($arraysToProcess[$idx]);
		}

		// Final pass: collapse the loop-accumulator pattern where each iteration
		// produced a longer non-empty list variant. When several non-empty list
		// ConstantArrayTypes survive earlier merging and together push the
		// constant-array value count past the limit, fold them into a single
		// non-empty-list<unionValueType> so the result stays bounded without
		// going through the lossier optimizeConstantArrays generalization.
		// Skip when every list variant shares one key signature — those collapse
		// losslessly via the stage 1 same-key-set merge in optimizeConstantArrays
		// (each position keeps its own value union), which is strictly more
		// precise than this flat fold.
		if ($preserveTaggedUnions && count($arraysToProcess) > 1) {
			$listVariantIndices = [];
			$listValueTypes = [];
			$listVariants = [];
			$listVariantSignatures = [];
			foreach ($arraysToProcess as $idx => $arr) {
				if (!$arr->isList()->yes() || !$arr->isIterableAtLeastOnce()->yes()) {
					continue;
				}
				$listVariantIndices[] = $idx;
				$listValueTypes[] = $arr->getIterableValueType();
				$listVariants[] = $arr;
				$signatureParts = [];
				foreach ($arr->getKeyTypes() as $i => $keyType) {
					$signatureParts[] = ($arr->isOptionalKey($i) ? '?' : '!') . ($keyType instanceof ConstantIntegerType ? 'i' : 's') . $keyType->getValue();
				}
				$listVariantSignatures[implode(',', $signatureParts)] = true;
			}
			if (
				count($listVariantIndices) >= 2
				&& count($listVariantSignatures) >= 2
				&& self::countConstantArrayValueTypes($listVariants) > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT
			) {
				$mergedValueType = self::union(...$listValueTypes);
				$merged = self::intersect(
					new ArrayType(new IntegerType(), $mergedValueType),
					new NonEmptyArrayType(),
					new AccessoryArrayListType(),
				);
				$newArrays[] = $merged;
				foreach ($listVariantIndices as $idx) {
					unset($arraysToProcess[$idx]);
				}
			}
		}

		return array_merge($newArrays, $arraysToProcess);
	}

	public static function intersect(Type ...$types): Type
	{
		$typesCount = count($types);
		if ($typesCount === 0) {
			return new NeverType();
		}

		$types = array_values($types);
		if ($typesCount === 1) {
			return $types[0];
		}

		foreach ($types as $type) {
			if ($type instanceof NeverType && !$type->isExplicit()) {
				return $type;
			}
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

		$newTypes = [];
		$hasOffsetValueTypeCount = 0;
		$typesCount = count($types);
		for ($i = 0; $i < $typesCount; $i++) {
			$type = $types[$i];

			if ($type instanceof IntersectionType && !$type instanceof TemplateType) {
				// transform A & (B & C) to A & B & C
				array_splice($types, $i--, 1, $type->getTypes());
				$typesCount = count($types);
			} elseif ($type instanceof HasOffsetValueType) {
				$hasOffsetValueTypeCount++;
			} else {
				$newTypes[] = $type;
			}
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

					if ($types[$i] instanceof ConstantArrayType && $types[$j] instanceof AccessoryArrayListType) {
						$types[$i] = $types[$i]->makeList();
						array_splice($types, $j--, 1);
						$typesCount--;
						continue;
					}

					if ($types[$j] instanceof ConstantArrayType && $types[$i] instanceof AccessoryArrayListType) {
						$types[$j] = $types[$j]->makeList();
						array_splice($types, $i--, 1);
						$typesCount--;
						continue 2;
					}

					if (
						$types[$i] instanceof ConstantArrayType
						&& $types[$j] instanceof NonEmptyArrayType
						&& (count($types[$i]->getKeyTypes()) === 1 || $types[$i]->isList()->yes())
						&& $types[$i]->isOptionalKey(0)
						&& !$types[$i]->isUnsealed()->yes()
					) {
						$types[$i] = $types[$i]->makeOffsetRequired($types[$i]->getKeyTypes()[0]);
						array_splice($types, $j--, 1);
						$typesCount--;
						continue;
					}

					if (
						$types[$j] instanceof ConstantArrayType
						&& $types[$i] instanceof NonEmptyArrayType
						&& (count($types[$j]->getKeyTypes()) === 1 || $types[$j]->isList()->yes())
						&& $types[$j]->isOptionalKey(0)
						&& !$types[$j]->isUnsealed()->yes()
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

					$constArrayIsI = $types[$i] instanceof ConstantArrayType && ($types[$j] instanceof ArrayType || $types[$j] instanceof ConstantArrayType);
					$constArrayIsJ = $types[$j] instanceof ConstantArrayType && ($types[$i] instanceof ArrayType || $types[$i] instanceof ConstantArrayType);
					if ($constArrayIsI || $constArrayIsJ) {
						$constArray = $constArrayIsI ? $types[$i] : $types[$j];
						$otherArray = $constArrayIsI ? $types[$j] : $types[$i];

						if (
							$otherArray instanceof ConstantArrayType
							&& !$constArray->isUnsealed()->maybe()
							&& !$otherArray->isUnsealed()->maybe()
						) {
							$merged = self::intersectDefiniteConstantArrays($constArray, $otherArray);
							if ($merged instanceof NeverType) {
								if ($merged->getReason() === null) {
									$reasons = array_merge($isSuperTypeA->reasons, $isSuperTypeB->reasons);
									if ($reasons !== []) {
										return new NeverType(reason: $reasons[0]);
									}
								}
								return $merged;
							}
							$newArrayType = $merged;
						} else {
							$newArray = ConstantArrayTypeBuilder::createEmpty();
							// Preserve unsealed extras from the source shape so the
							// rebuild doesn't silently turn `array{k: int, ...} & X`
							// into a sealed `array{k: int}` — intersect with the other
							// side's iterable key/value so the open part keeps both
							// sides' refinements.
							$constUnsealed = $constArray->getUnsealedTypes();
							if ($constUnsealed !== null && $constArray->isUnsealed()->yes()) {
								$newUnsealedKey = self::intersect($constUnsealed[0], $otherArray->getIterableKeyType());
								$newUnsealedValue = self::intersect($constUnsealed[1], $otherArray->getIterableValueType());
								if (!$newUnsealedKey instanceof NeverType && !$newUnsealedValue instanceof NeverType) {
									$newArray->makeUnsealed($newUnsealedKey, $newUnsealedValue);
								}
							}
							$valueTypes = $constArray->getValueTypes();
							foreach ($constArray->getKeyTypes() as $k => $keyType) {
								$hasOffset = $otherArray->hasOffsetValueType($keyType);
								if ($hasOffset->no()) {
									continue;
								}
								$newArray->setOffsetValueType(
									self::intersect($keyType, $otherArray->getIterableKeyType()),
									self::intersect($valueTypes[$k], $otherArray->getOffsetValueType($keyType)),
									$constArray->isOptionalKey($k) && !$hasOffset->yes(),
								);
							}
							$newArrayType = $newArray->getArray();
						}

						if ($constArrayIsI) {
							$types[$i] = $newArrayType;
							array_splice($types, $j--, 1);
						} else {
							$types[$j] = $newArrayType;
							array_splice($types, $i--, 1);
						}
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
					return new NeverType(reason: $isSuperTypeA->reasons[0] ?? null);
				}
			}
		}

		if ($typesCount === 1) {
			return $types[0];
		}

		$accessoryBaseTypes = [];
		foreach ($types as $type) {
			if (!$type instanceof AccessoryType) {
				$accessoryBaseTypes = null;
				break;
			}
			$accessoryBaseTypes[] = $type->getDefaultBaseType();
		}
		if ($accessoryBaseTypes !== null) {
			// Accessory types never stand alone — supply the base type they refine.
			return self::intersect(self::intersect(...$accessoryBaseTypes), ...$types);
		}

		return new IntersectionType($types);
	}

	private static function intersectDefiniteConstantArrays(ConstantArrayType $a, ConstantArrayType $b): Type
	{
		$aSealed = $a->isUnsealed()->no();
		$bSealed = $b->isUnsealed()->no();
		$bothUnsealed = !$aSealed && !$bSealed && $a->getUnsealedTypes() !== null && $b->getUnsealedTypes() !== null;

		$aKeyByValue = [];
		foreach ($a->getKeyTypes() as $k => $keyType) {
			$aKeyByValue[$keyType->getValue()] = $k;
		}
		$bKeyByValue = [];
		foreach ($b->getKeyTypes() as $k => $keyType) {
			$bKeyByValue[$keyType->getValue()] = $k;
		}

		if ($aSealed && $bSealed) {
			foreach ($aKeyByValue as $keyValue => $k) {
				if (!$a->isOptionalKey($k) && !array_key_exists($keyValue, $bKeyByValue)) {
					return new NeverType();
				}
			}
			foreach ($bKeyByValue as $keyValue => $k) {
				if (!$b->isOptionalKey($k) && !array_key_exists($keyValue, $aKeyByValue)) {
					return new NeverType();
				}
			}
		}

		$newArray = ConstantArrayTypeBuilder::createEmpty();

		if ($bothUnsealed) {
			$aUnsealed = $a->getUnsealedTypes();
			$bUnsealed = $b->getUnsealedTypes();
			$unsealedKey = self::intersect($aUnsealed[0], $bUnsealed[0]);
			$unsealedValue = self::intersect($aUnsealed[1], $bUnsealed[1]);
			if ($unsealedKey instanceof NeverType || $unsealedValue instanceof NeverType) {
				return new NeverType();
			}
			$newArray->makeUnsealed($unsealedKey, $unsealedValue);
		} else {
			$never = new NeverType(true);
			$newArray->makeUnsealed($never, $never);
		}

		$resolveOtherValue = static function (ConstantArrayType $other, Type $keyType): ?Type {
			if ($other->hasOffsetValueType($keyType)->yes()) {
				return $other->getOffsetValueType($keyType);
			}
			$otherUnsealed = $other->getUnsealedTypes();
			if ($otherUnsealed === null) {
				return null;
			}
			[$unsealedKey, $unsealedValue] = $otherUnsealed;
			if ($unsealedKey instanceof NeverType && $unsealedKey->isExplicit()) {
				return null;
			}
			if ($unsealedKey->isSuperTypeOf($keyType)->no()) {
				return null;
			}
			return $unsealedValue;
		};

		$keysToProcess = [];
		foreach ($aKeyByValue as $keyValue => $k) {
			$keysToProcess[$keyValue] = [$k, $bKeyByValue[$keyValue] ?? null];
		}
		foreach ($bKeyByValue as $keyValue => $k) {
			if (array_key_exists($keyValue, $keysToProcess)) {
				continue;
			}

			$keysToProcess[$keyValue] = [null, $k];
		}

		foreach ($keysToProcess as [$aIdx, $bIdx]) {
			if ($aIdx !== null && $bIdx !== null) {
				$keyType = $a->getKeyTypes()[$aIdx];
				$value = self::intersect($a->getValueTypes()[$aIdx], $b->getValueTypes()[$bIdx]);
				$optional = $a->isOptionalKey($aIdx) && $b->isOptionalKey($bIdx);
			} elseif ($aIdx !== null) {
				$keyType = $a->getKeyTypes()[$aIdx];
				$aValue = $a->getValueTypes()[$aIdx];
				$bValue = $resolveOtherValue($b, $keyType);
				if ($bValue === null) {
					if ($a->isOptionalKey($aIdx)) {
						continue;
					}
					return new NeverType();
				}
				$value = self::intersect($aValue, $bValue);
				$optional = $a->isOptionalKey($aIdx);
			} else {
				/** @var int<0, max> $bIdx */
				$keyType = $b->getKeyTypes()[$bIdx];
				$bValue = $b->getValueTypes()[$bIdx];
				$aValue = $resolveOtherValue($a, $keyType);
				if ($aValue === null) {
					if ($b->isOptionalKey($bIdx)) {
						continue;
					}
					return new NeverType();
				}
				$value = self::intersect($aValue, $bValue);
				$optional = $b->isOptionalKey($bIdx);
			}

			if ($value instanceof NeverType) {
				if ($optional) {
					continue;
				}
				return new NeverType();
			}
			$newArray->setOffsetValueType($keyType, $value, $optional);
		}

		return $newArray->getArray();
	}

	/**
	 * Merge two IntersectionTypes that have the same structure but differ
	 * in HasOffsetValueType value types (matched by offset key).
	 *
	 * E.g. (A & hasOV('k', X)) | (A & hasOV('k', Y)) → (A & hasOV('k', X|Y))
	 */
	private static function mergeIntersectionsForUnion(IntersectionType $a, IntersectionType $b): ?Type
	{
		$aTypes = $a->getTypes();
		$bTypes = $b->getTypes();

		if (count($aTypes) !== count($bTypes)) {
			return null;
		}

		$mergedTypes = [];
		$hasDifference = false;
		$bUsed = array_fill(0, count($bTypes), false);

		foreach ($aTypes as $aType) {
			$matched = false;
			foreach ($bTypes as $bIdx => $bType) {
				if ($bUsed[$bIdx]) {
					continue;
				}

				if ($aType->equals($bType)) {
					$mergedTypes[] = $aType;
					$bUsed[$bIdx] = true;
					$matched = true;
					break;
				}

				// HasOffsetValueType: merge value types when offset keys match
				if ($aType instanceof HasOffsetValueType && $bType instanceof HasOffsetValueType
					&& $aType->getOffsetType()->equals($bType->getOffsetType())) {
					$mergedTypes[] = new HasOffsetValueType(
						$aType->getOffsetType(),
						self::union($aType->getValueType(), $bType->getValueType()),
					);
					$hasDifference = true;
					$bUsed[$bIdx] = true;
					$matched = true;
					break;
				}

				// HasOffsetType, HasMethodType, HasPropertyType: only equal values match (no merging possible)
			}
			if (!$matched) {
				return null;
			}
		}

		if (!$hasDifference) {
			return null;
		}

		$result = $mergedTypes[0];
		for ($i = 1, $count = count($mergedTypes); $i < $count; $i++) {
			$result = self::intersect($result, $mergedTypes[$i]);
		}
		return $result;
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
