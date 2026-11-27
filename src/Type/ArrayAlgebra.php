<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\Combinator\SingleValueUnionRule;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Generic\TemplateType;
use function array_key_exists;
use function array_merge;
use function count;
use function get_class;
use function min;

/**
 * The unions of two shapes as one shape, and the intersections of an array
 * with an accessory type that the array takes in whole: the tryUnion() of
 * ConstantArrayType, and the tryIntersect() of ConstantArrayType and
 * OversizedArrayType.
 *
 * The intersections apply where neither side holds the other - where one does,
 * intersect() keeps the subtype as it is.
 *
 * @internal
 */
#[ReferencedByTurboExtension(key: 'arrayAlgebra')]
final class ArrayAlgebra
{

	/** The most optional keys whose sets tryUnionConstantArrayBesideEmptyArray() counts in an integer. */
	private const KEY_SETS_COUNTED_LIMIT = 62;

	/** The most shapes splitConstantArrayAtFiniteValue() splits a shape into, over all of its keys. */
	private const SPLIT_SHAPES_LIMIT = 64;

	/**
	 * The shapes of a shape, one per value of its first key whose value is a
	 * union of finitely many values: `array{type: 'a'|'b', x: int}` is
	 * `array{type: 'a', x: int}` and `array{type: 'b', x: int}`.
	 *
	 * tryUnionConstantArray() merges shapes that differ at one key into one
	 * shape, which a union of the shapes it merged no longer accepts member by
	 * member - UnionType::accepts() splits it back with this. Null when no key
	 * has such a value, or when the shape splits into more than
	 * SPLIT_SHAPES_LIMIT shapes over all of its keys.
	 *
	 * @return list<ConstantArrayType>|null
	 */
	public static function splitConstantArrayAtFiniteValue(ConstantArrayType $array): ?array
	{
		$valueTypes = $array->getValueTypes();
		$splitIndex = null;
		$splitValues = [];
		$shapesCount = 1;
		foreach ($valueTypes as $i => $valueType) {
			// unions of constant scalars, the values tryUnionConstantArray() unions at one key
			if (!$valueType instanceof UnionType || $valueType instanceof TemplateType || !$valueType->isConstantScalarValue()->yes()) {
				continue;
			}
			$finiteTypes = $valueType->getFiniteTypes();
			if (count($finiteTypes) < 2) {
				continue;
			}
			$shapesCount *= count($finiteTypes);
			if ($shapesCount > self::SPLIT_SHAPES_LIMIT) {
				return null;
			}
			if ($splitIndex !== null) {
				continue;
			}
			$splitIndex = $i;
			$splitValues = $finiteTypes;
		}
		if ($splitIndex === null) {
			return null;
		}

		$shapes = [];
		foreach ($splitValues as $splitValue) {
			$valueTypes[$splitIndex] = $splitValue;
			$shapes[] = new ConstantArrayType(
				$array->getKeyTypes(),
				$valueTypes,
				$array->getNextAutoIndexes(),
				$array->getOptionalKeys(),
				$array->isList(),
				$array->getUnsealedTypes(),
			);
		}

		return $shapes;
	}

	/** @see Type::tryIntersect() */
	public static function tryIntersectConstantArray(ConstantArrayType $array, Type $otherType): ?Type
	{
		if ($otherType instanceof ConstantArrayType || $otherType instanceof ArrayType) {
			return self::tryIntersectShapeWithArray($array, $otherType);
		}
		if ($otherType instanceof IterableType) {
			return self::tryIntersectIterables($array, $otherType);
		}
		if (
			!$otherType instanceof HasOffsetType
			&& !$otherType instanceof HasOffsetValueType
			&& !$otherType instanceof AccessoryArrayListType
			&& !$otherType instanceof NonEmptyArrayType
		) {
			return null;
		}
		if (!self::overlapsWithoutContaining($array, $otherType)) {
			return null;
		}

		if ($otherType instanceof HasOffsetType) {
			return $array->makeOffsetRequired($otherType->getOffsetType());
		}

		if ($otherType instanceof AccessoryArrayListType) {
			return $array->makeList();
		}

		if ($otherType instanceof NonEmptyArrayType) {
			// the one key that can make it non-empty
			if (
				(count($array->getKeyTypes()) === 1 || $array->isList()->yes())
				&& $array->isOptionalKey(0)
				&& !$array->isUnsealed()->yes()
			) {
				return $array->makeOffsetRequired($array->getKeyTypes()[0]);
			}

			return null;
		}

		$offsetType = $otherType->getOffsetType();
		$valueType = TypeCombinator::intersect($array->getOffsetValueType($offsetType), $otherType->getValueType());
		if ($valueType instanceof NeverType) {
			return $valueType;
		}

		return $array->setOffsetValueType($offsetType, $valueType);
	}

	/**
	 * An array with a value of A or one of B at an offset is an array with a
	 * value of A|B there.
	 *
	 * @see Type::tryUnion()
	 */
	public static function tryUnionHasOffsetValue(HasOffsetValueType $type, Type $otherType): ?Type
	{
		if (!$otherType instanceof HasOffsetValueType || !$type->getOffsetType()->equals($otherType->getOffsetType())) {
			return null;
		}

		return new HasOffsetValueType($type->getOffsetType(), TypeCombinator::union($type->getValueType(), $otherType->getValueType()));
	}

	/**
	 * Two arrays or iterables neither holding the other are the one of the
	 * keys and the values both allow, and a general array is a list of the
	 * keys a list allows.
	 *
	 * @see Type::tryIntersect()
	 */
	public static function tryIntersectArray(ArrayType $array, Type $otherType): ?Type
	{
		if ($otherType instanceof ArrayType || $otherType instanceof IterableType) {
			return self::tryIntersectIterables($array, $otherType);
		}
		if (
			!$otherType instanceof AccessoryArrayListType
			|| get_class($array) !== ArrayType::class
			|| !self::overlapsNeitherContaining($array, $otherType)
			|| $otherType->getIterableKeyType()->isSuperTypeOf($array->getIterableKeyType())->yes()
		) {
			return null;
		}

		$keyType = TypeCombinator::intersect($array->getIterableKeyType(), $otherType->getIterableKeyType());
		if ($keyType instanceof NeverType) {
			return $keyType;
		}

		return TypeCombinator::intersect(new ArrayType($keyType, $array->getItemType()), $otherType);
	}

	/** @see Type::tryIntersect() */
	public static function tryIntersectIterable(IterableType $iterable, Type $otherType): ?Type
	{
		if (!$otherType instanceof ArrayType && !$otherType instanceof ConstantArrayType && !$otherType instanceof IterableType) {
			return null;
		}

		return self::tryIntersectIterables($iterable, $otherType);
	}

	/**
	 * Of an array or iterable and another, the one of the keys and the values
	 * both allow: an iterable of two iterables, an array otherwise.
	 */
	private static function tryIntersectIterables(ArrayType|ConstantArrayType|IterableType $type, ArrayType|ConstantArrayType|IterableType $otherType): ?Type
	{
		// two equal arrays or iterables hold each other: the other one stands
		// for both, as it does when a member holding another gives way to it
		if (
			get_class($type) === get_class($otherType)
			&& (get_class($type) === ArrayType::class || get_class($type) === IterableType::class)
			&& $type->equals($otherType)
		) {
			return $otherType;
		}

		if (!self::overlapsNeitherContaining($type, $otherType)) {
			return null;
		}

		$keyType = TypeCombinator::intersect($otherType->getIterableKeyType(), $type->getKeyType());
		$itemType = TypeCombinator::intersect($otherType->getItemType(), $type->getItemType());
		if ($type instanceof IterableType && $otherType instanceof IterableType) {
			return new IterableType($keyType, $itemType);
		}

		return new ArrayType($keyType, $itemType);
	}

	/**
	 * A shape and an array: the one the other holds, as both are compared
	 * anyway, and of two neither holding the other the shape with the values at
	 * its keys narrowed to the values the other array allows there, or never
	 * when one of its required keys cannot be there.
	 */
	private static function tryIntersectShapeWithArray(ConstantArrayType $constArray, Type $otherArray): ?Type
	{
		$isSuperTypeA = $otherArray->isSuperTypeOf($constArray);
		if ($isSuperTypeA->yes()) {
			return $constArray;
		}
		$isSuperTypeB = $constArray->isSuperTypeOf($otherArray);
		if ($isSuperTypeB->yes()) {
			return $otherArray;
		}
		if ($isSuperTypeA->no() && $isSuperTypeB->no()) {
			return null;
		}

		$otherConstantArray = $otherArray instanceof ConstantArrayType ? $otherArray : null;
		if (
			$otherConstantArray !== null
			&& !$constArray->isUnsealed()->maybe()
			&& !$otherConstantArray->isUnsealed()->maybe()
		) {
			$merged = self::intersectDefiniteConstantArrays($constArray, $otherConstantArray);
			if ($merged instanceof NeverType && $merged->getReason() === null) {
				$reasons = array_merge($isSuperTypeA->getReasons(), $isSuperTypeB->getReasons());
				if ($reasons !== []) {
					return new NeverType(reason: $reasons[0]);
				}
			}

			return $merged;
		}

		$newArray = ConstantArrayTypeBuilder::createEmpty();
		// Preserve unsealed extras from the source shape so the
		// rebuild doesn't silently turn `array{k: int, ...} & X`
		// into a sealed `array{k: int}` — intersect with the other
		// side's iterable key/value so the open part keeps both
		// sides' refinements.
		$constUnsealed = $constArray->getUnsealedTypes();
		if ($constUnsealed !== null && $constArray->isUnsealed()->yes()) {
			$newUnsealedKey = TypeCombinator::intersect($constUnsealed[0], $otherArray->getIterableKeyType());
			$newUnsealedValue = TypeCombinator::intersect($constUnsealed[1], $otherArray->getIterableValueType());
			if (!$newUnsealedKey instanceof NeverType && !$newUnsealedValue instanceof NeverType) {
				$newArray->makeUnsealed($newUnsealedKey, $newUnsealedValue);
			}
		}
		$valueTypes = $constArray->getValueTypes();
		foreach ($constArray->getKeyTypes() as $k => $keyType) {
			$hasOffset = $otherArray->hasOffsetValueType($keyType);
			if ($hasOffset->no()) {
				// a shape that may have keys it does not list answers No for them too
				if ($otherConstantArray !== null && $otherConstantArray->isUnsealed()->maybe()) {
					$newArray->setOffsetValueType($keyType, $valueTypes[$k], $constArray->isOptionalKey($k));
					continue;
				}
				if (!$constArray->isOptionalKey($k)) {
					return new NeverType();
				}
				continue;
			}
			$newArray->setOffsetValueType(
				TypeCombinator::intersect($keyType, $otherArray->getIterableKeyType()),
				TypeCombinator::intersect($valueTypes[$k], $otherArray->getOffsetValueType($keyType)),
				$constArray->isOptionalKey($k) && !$hasOffset->yes(),
			);
		}
		if ($otherConstantArray !== null && $constArray->isUnsealed()->maybe()) {
			// keys the shape does not list keep the other shape's values
			$otherValueTypes = $otherConstantArray->getValueTypes();
			foreach ($otherConstantArray->getKeyTypes() as $k => $keyType) {
				if (!$constArray->hasOffsetValueType($keyType)->no()) {
					continue;
				}
				$newArray->setOffsetValueType($keyType, $otherValueTypes[$k], $otherConstantArray->isOptionalKey($k));
			}
		}
		$newArrayType = $newArray->getArray();
		if ($constArray->isList()->yes()) {
			$newArrayType = TypeCombinator::intersect($newArrayType, new AccessoryArrayListType());
		}

		return $newArrayType;
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
			$unsealedKey = TypeCombinator::intersect($aUnsealed[0], $bUnsealed[0]);
			$unsealedValue = TypeCombinator::intersect($aUnsealed[1], $bUnsealed[1]);
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
				$value = TypeCombinator::intersect($a->getValueTypes()[$aIdx], $b->getValueTypes()[$bIdx]);
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
				$value = TypeCombinator::intersect($aValue, $bValue);
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
				$value = TypeCombinator::intersect($aValue, $bValue);
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

		$newArrayType = $newArray->getArray();
		// optional keys rebuilt one by one may each be left out alone, so the
		// builder cannot tell a list from the keys - the inputs can
		if ($a->isList()->yes() || $b->isList()->yes()) {
			$newArrayType = TypeCombinator::intersect($newArrayType, new AccessoryArrayListType());
		}

		return $newArrayType;
	}

	/** @see Type::tryIntersect() */
	public static function tryIntersectOversizedArray(OversizedArrayType $array, Type $otherType): ?Type
	{
		// an oversized array no longer tracks its offsets
		if (!$otherType instanceof HasOffsetValueType || !self::overlapsWithoutContaining($array, $otherType)) {
			return null;
		}

		return $array;
	}

	/**
	 * The union of two shapes as one shape when that loses nothing: when one
	 * holds the other, or when they differ at a single key only - in its value,
	 * whether it is optional, or whether it is there at all - where the merged
	 * shape has the union of the values, optional unless both require it.
	 * Shapes differing at more keys stay a tagged union.
	 *
	 * An empty array merges only into a shape that already holds it:
	 * `array{}|array{a: int}` keeps it apart. Operations on a shape, such as
	 * appending to a list, are exact on each member of a union but not on
	 * optional keys, and `$a = []; if (...) { $a[] = 1; }` is the most common
	 * union of shapes.
	 *
	 * Shapes with unsealed extras are left to TypeCombinator.
	 *
	 * @see Type::tryUnion()
	 */
	public static function tryUnionConstantArray(ConstantArrayType $array, Type $otherType): ?Type
	{
		// a bare ConstantArrayType is the only type whose getConstantArrays() is itself
		$otherShapes = $otherType->getConstantArrays();
		if ($otherShapes !== [$otherType]) {
			return null;
		}
		$otherType = $otherShapes[0];
		if (
			$array instanceof TemplateType
			|| $otherType instanceof TemplateType
			|| $array->isUnsealed()->yes()
			|| $otherType->isUnsealed()->yes()
		) {
			return null;
		}

		// $bigger has every key of $smaller, or they cannot merge
		[$bigger, $smaller] = count($array->getKeyTypes()) >= count($otherType->getKeyTypes())
			? [$array, $otherType]
			: [$otherType, $array];
		$smallerKeys = [];
		foreach ($smaller->getKeyTypes() as $j => $keyType) {
			$smallerKeys[$keyType->getValue()] = $j;
		}

		$smallerValueTypes = $smaller->getValueTypes();
		$biggerValueTypes = $bigger->getValueTypes();
		$matchedKeys = 0;
		$differingKeys = 0;
		// a key of $bigger only it requires keeps it from holding $smaller
		$biggerRequiresOtherKey = false;
		// two different single values at a key keep either from holding the other
		$hasDifferentValues = false;
		foreach ($bigger->getKeyTypes() as $i => $keyType) {
			$j = $smallerKeys[$keyType->getValue()] ?? null;
			if ($j === null) {
				$differingKeys++;
				$biggerRequiresOtherKey = $biggerRequiresOtherKey || !$bigger->isOptionalKey($i);
				continue;
			}
			$matchedKeys++;
			if (
				$bigger->isOptionalKey($i) === $smaller->isOptionalKey($j)
				&& $biggerValueTypes[$i]->equals($smallerValueTypes[$j])
			) {
				continue;
			}
			$differingKeys++;
			// a union is not a single value, and asking would visit its members
			if ($hasDifferentValues || $biggerValueTypes[$i] instanceof UnionType || $smallerValueTypes[$j] instanceof UnionType) {
				continue;
			}
			$biggerValueKey = SingleValueUnionRule::getValueKey($biggerValueTypes[$i]);
			$smallerValueKey = $biggerValueKey !== null ? SingleValueUnionRule::getValueKey($smallerValueTypes[$j]) : null;
			$hasDifferentValues = $smallerValueKey !== null && $biggerValueKey !== $smallerValueKey;
		}
		if ($matchedKeys !== count($smallerKeys)) {
			return null;
		}

		if ($differingKeys === 0) {
			return $bigger;
		}

		if ($differingKeys === 1 && $smallerKeys !== []) {
			$merged = $bigger->mergeWith($smaller);
			if (
				count($biggerValueTypes) === 1
				&& $merged->getValueTypes()[0] instanceof UnionType
				&& $biggerValueTypes[0]->isSuperTypeOf($smallerValueTypes[0])->no()
			) {
				// `array{'a'}|array{'b'}` stays apart: functions that make keys of the
				// values, such as array_fill_keys(), tell the shapes apart by them
				return null;
			}

			return $merged;
		}

		if (self::areListsOfOneLengthRange($bigger, $smaller)) {
			return $bigger->mergeWith($smaller);
		}

		if ($hasDifferentValues) {
			return null;
		}
		if (!$biggerRequiresOtherKey && $bigger->isSuperTypeOf($smaller)->yes()) {
			return $bigger;
		}
		if ($matchedKeys === count($bigger->getKeyTypes()) && $smaller->isSuperTypeOf($bigger)->yes()) {
			return $smaller;
		}

		return null;
	}

	/**
	 * The union of two shapes as one shape in a union that holds the empty
	 * array as well: when the merged shape holds the two and at most the empty
	 * array besides, such as `array{a: 1}|array{a?: 1, b: 2}` beside
	 * `array{}`, which together are `array{a?: 1, b?: 2}`.
	 *
	 * The shapes are not empty and not lists - a list holds the lists of its lengths, which
	 * tryUnionConstantArray() merges - and have the same value at each key
	 * they share, so the shapes they hold are the sets of keys from their
	 * required keys to all their keys, and counting those sets tells whether
	 * the merged shape holds any other.
	 */
	public static function tryUnionConstantArrayBesideEmptyArray(ConstantArrayType $array, ConstantArrayType $otherType): ?ConstantArrayType
	{
		if (
			$array instanceof TemplateType
			|| $otherType instanceof TemplateType
			|| $array->isUnsealed()->yes()
			|| $otherType->isUnsealed()->yes()
			|| $array->isList()->yes()
			|| $otherType->isList()->yes()
			// the empty array itself stays apart, see tryUnionConstantArray()
			|| $array->getKeyTypes() === []
			|| $otherType->getKeyTypes() === []
		) {
			return null;
		}

		[$bigger, $smaller] = count($array->getKeyTypes()) >= count($otherType->getKeyTypes())
			? [$array, $otherType]
			: [$otherType, $array];
		$biggerKeys = [];
		foreach ($bigger->getKeyTypes() as $i => $keyType) {
			$biggerKeys[$keyType->getValue()] = $i;
		}

		$biggerValueTypes = $bigger->getValueTypes();
		$smallerValueTypes = $smaller->getValueTypes();
		$biggerRequired = count($biggerKeys) - count($bigger->getOptionalKeys());
		$smallerRequired = 0;
		// the keys both require, and the keys either requires
		$bothRequire = 0;
		$eitherRequires = $biggerRequired;
		$biggerRequiredInSmaller = 0;
		foreach ($smaller->getKeyTypes() as $j => $keyType) {
			$i = $biggerKeys[$keyType->getValue()] ?? null;
			if ($i === null || !$biggerValueTypes[$i]->equals($smallerValueTypes[$j])) {
				return null;
			}
			if (!$bigger->isOptionalKey($i)) {
				$biggerRequiredInSmaller++;
			}
			if ($smaller->isOptionalKey($j)) {
				continue;
			}
			$smallerRequired++;
			if ($bigger->isOptionalKey($i)) {
				$eitherRequires++;
			} else {
				$bothRequire++;
			}
		}
		// the sets of keys of the merged shape and those of both, without the empty one
		$biggerCount = count($biggerKeys);
		$smallerCount = count($smaller->getKeyTypes());
		if ($biggerCount - $bothRequire > self::KEY_SETS_COUNTED_LIMIT) {
			return null;
		}
		$merged = (1 << $biggerCount - $bothRequire) - ($bothRequire === 0 ? 1 : 0);
		// the sets of keys both hold: from the keys either requires to the
		// keys of the smaller one, when it has all the keys the bigger requires
		$shared = $biggerRequiredInSmaller === $biggerRequired ? 1 << $smallerCount - $eitherRequires : 0;
		$union = (1 << $biggerCount - $biggerRequired)
			+ (1 << $smallerCount - $smallerRequired)
			- $shared
			- ($biggerRequired === 0 || $smallerRequired === 0 ? 1 : 0);
		if ($merged !== $union) {
			return null;
		}

		return $bigger->mergeWith($smaller);
	}

	/**
	 * Whether two lists are one list of the lengths of both: a list shape holds
	 * the lists of each length from its required keys to all its keys, the
	 * value at each position being its value type there. The merged list,
	 * with the union of the values at each position, holds exactly the two when
	 * at each length it holds, the lists of that length the two hold are one
	 * product of values: one list's, or both when one holds the other's or
	 * they differ at one position below that length only.
	 *
	 * $bigger has every key of $smaller.
	 */
	private static function areListsOfOneLengthRange(ConstantArrayType $bigger, ConstantArrayType $smaller): bool
	{
		if (!$bigger->isList()->yes() || !$smaller->isList()->yes()) {
			return false;
		}

		$biggerMax = count($bigger->getKeyTypes());
		$smallerMax = count($smaller->getKeyTypes());
		$biggerMin = $biggerMax - count($bigger->getOptionalKeys());
		$smallerMin = $smallerMax - count($smaller->getOptionalKeys());
		if ($smallerMax === 0 || $smallerMax < $biggerMin - 1) {
			// the empty array stays apart, see tryUnionConstantArray(); or a
			// length of neither between them
			return false;
		}

		// a list's keys are its positions in order
		$biggerValueTypes = $bigger->getValueTypes();
		$smallerValueTypes = $smaller->getValueTypes();
		$minLength = min($biggerMin, $smallerMin);
		// over the positions below the length
		$differingPositions = 0;
		$biggerHoldsSmaller = true;
		$smallerHoldsBigger = true;
		for ($length = 1; $length <= $biggerMax; $length++) {
			$i = $length - 1;
			if ($i < $smallerMax && !$biggerValueTypes[$i]->equals($smallerValueTypes[$i])) {
				$differingPositions++;
				$biggerHoldsSmaller = $biggerHoldsSmaller && $biggerValueTypes[$i]->isSuperTypeOf($smallerValueTypes[$i])->yes();
				$smallerHoldsBigger = $smallerHoldsBigger && $smallerValueTypes[$i]->isSuperTypeOf($biggerValueTypes[$i])->yes();
			}
			if ($length < $minLength) {
				continue;
			}

			$inBigger = $length >= $biggerMin;
			$inSmaller = $length >= $smallerMin && $length <= $smallerMax;
			if ($inBigger && $inSmaller) {
				if ($differingPositions > 1 && !$biggerHoldsSmaller && !$smallerHoldsBigger) {
					return false;
				}
			} elseif ($inBigger) {
				if (!$biggerHoldsSmaller) {
					return false;
				}
			} elseif (!$smallerHoldsBigger) {
				return false;
			}
		}

		return true;
	}

	private static function overlapsWithoutContaining(Type $array, Type $otherType): bool
	{
		return !$otherType->isSuperTypeOf($array)->yes() && $array->isSuperTypeOf($otherType)->maybe();
	}

	/** Whether the two types may have values in common and neither holds the other. */
	private static function overlapsNeitherContaining(Type $type, Type $otherType): bool
	{
		$isSuperType = $type->isSuperTypeOf($otherType);
		if ($isSuperType->yes()) {
			return false;
		}
		$isSubType = $otherType->isSuperTypeOf($type);

		return !$isSubType->yes() && ($isSuperType->maybe() || $isSubType->maybe());
	}

}
