<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\ArrayAlgebra;
use PHPStan\Type\ArrayType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Generic\TemplateArrayType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeKind;
use PHPStan\Type\VerbosityLevel;
use function array_filter;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_merge;
use function array_values;
use function count;
use function crc32;
use function is_int;
use function ksort;
use function serialize;
use function spl_object_id;
use function sprintf;

/**
 * What the array rules of TypeCombinator::union() and widen() share: which
 * members are arrays, the accessory types the arrays have in common, and the
 * exact union of shapes.
 *
 * @internal
 */
final class ArrayUnion
{

	/** A shape with a value that is not a single value. */
	private const OTHER_VALUES = 0;

	/** A shape whose values are single values, with optional keys. */
	private const SINGLE_VALUES = 1;

	/** A shape whose values are single values, without optional keys: a single value itself. */
	private const SINGLE_VALUE_SHAPE = 2;

	/** The hash of a shape's keys and values, by which the shape is found. */
	private const HASH_SHAPE = 0;

	/** The hash of a shape's keys and values but one, by which it is found from a shape without that key. */
	private const HASH_WITH_KEY_MORE = 1;

	/** The hash of a shape's keys and values but one value, by which it is found from a shape with another value there. */
	private const HASH_OTHER_VALUE = 2;

	/**
	 * The members that are arrays, by their index. A late-resolvable type, such
	 * as a conditional type, stays as it is until it resolves: merged with the
	 * other arrays, it would lose its condition and its accessory types.
	 *
	 * @param list<Type> $types
	 * @return array<int, Type>
	 */
	public static function getArrayMembers(array $types): array
	{
		$arrays = [];
		foreach ($types as $i => $type) {
			if (($type->getKindMask() & ~TypeKind::ARRAY) !== 0 || !$type->isArray()->yes() || $type instanceof LateResolvableType) {
				continue;
			}
			$arrays[$i] = $type;
		}

		return $arrays;
	}

	/**
	 * The members other than the replaced ones, then the arrays the rule made.
	 *
	 * @param list<Type> $types
	 * @param array<int, mixed> $arrayMembers the replaced members by their index
	 * @param list<Type> $arrays
	 * @return list<Type>
	 */
	public static function replaceArrayMembers(array $types, array $arrayMembers, array $arrays): array
	{
		$result = [];
		foreach ($types as $i => $type) {
			if (isset($arrayMembers[$i])) {
				continue;
			}
			$result[] = $type;
		}

		return array_merge($result, $arrays);
	}

	/**
	 * The array members the array rules merge, by their index, and the indices
	 * of the members they drop. Next to an oversized array, the first empty
	 * array stays a member of its own and the other ones are dropped: folded
	 * into the oversized array, it would be lost to the oversized accessory,
	 * which accepts only arrays that hold something.
	 *
	 * @param list<Type> $types
	 * @return array{array<int, Type>, array<int, true>}
	 */
	public static function getMergeableArrays(array $types): array
	{
		$arrays = self::getArrayMembers($types);
		$emptyArray = self::findEmptyArrayNextToOversizedArray($arrays);
		if ($emptyArray === null) {
			return [$arrays, []];
		}

		$dropped = [];
		foreach ($arrays as $i => $arrayType) {
			if (!$arrayType->isConstantArray()->yes() || !$arrayType->isIterableAtLeastOnce()->no()) {
				continue;
			}
			unset($arrays[$i]);
			if ($arrayType === $emptyArray) {
				continue;
			}
			$dropped[$i] = true;
		}

		return [$arrays, $dropped];
	}

	/**
	 * @param Type[] $arrayTypes
	 */
	private static function findEmptyArrayNextToOversizedArray(array $arrayTypes): ?Type
	{
		if (count($arrayTypes) < 2) {
			return null;
		}

		$emptyArray = null;
		$hasOversizedArray = false;
		foreach ($arrayTypes as $arrayType) {
			if ($arrayType->isOversizedArray()->yes()) {
				$hasOversizedArray = true;
			} elseif ($emptyArray === null && $arrayType->isConstantArray()->yes() && $arrayType->isIterableAtLeastOnce()->no()) {
				$emptyArray = $arrayType;
			}
		}

		return $hasOversizedArray ? $emptyArray : null;
	}

	/**
	 * @param Type[] $arrayTypes
	 * @return list<Type>
	 */
	public static function getCommonAccessoryTypes(array $arrayTypes): array
	{
		$isIterableAtLeastOnce = [];
		$accessoryTypes = [];
		foreach ($arrayTypes as $i => $arrayType) {
			$isIterableAtLeastOnce[] = $arrayType->isIterableAtLeastOnce();

			if ($arrayType instanceof IntersectionType) {
				// asked once: the same for every hasOffset() member, and an
				// intersection computes it from all of its members
				$iterableValueType = null;
				foreach ($arrayType->getTypes() as $innerType) {
					if ($innerType instanceof TemplateType) {
						break;
					}
					if (!($innerType instanceof AccessoryType) && !($innerType instanceof CallableType)) {
						continue;
					}
					if ($innerType instanceof HasOffsetType) {
						$iterableValueType ??= $arrayType->getIterableValueType();
						$innerType = new HasOffsetValueType($innerType->getOffsetType(), $iterableValueType);
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
				// of one offset, so their union is the offset of all their
				// values, as HasOffsetValueType::tryUnion() merges two of them
				$offsetType = $accessoryType[0]->getOffsetType();
				$valueTypes = [];
				foreach ($accessoryType as $hasOffsetValue) {
					$valueTypes[] = $hasOffsetValue->getOffsetValueType($offsetType);
				}
				$commonAccessoryTypes[] = new HasOffsetValueType($offsetType, TypeCombinator::union(...$valueTypes));
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
	 * The array with the accessory types: an empty array cannot satisfy the
	 * non-empty and the oversized ones, and with them would be a contradictory
	 * intersection, such as `array{}&oversized-array`, rejecting the very value
	 * it represents.
	 *
	 * @param list<Type> $accessoryTypes
	 */
	public static function withAccessoryTypes(Type $array, array $accessoryTypes): Type
	{
		if ($array->isIterableAtLeastOnce()->no()) {
			$accessoryTypes = array_values(array_filter(
				$accessoryTypes,
				static fn (Type $t): bool => !($t instanceof OversizedArrayType) && !($t instanceof NonEmptyArrayType),
			));
		}

		return TypeCombinator::intersect($array, ...$accessoryTypes);
	}

	/**
	 * One array of all the keys and all the values of the arrays, with the
	 * accessory types they have in common.
	 *
	 * @param list<Type> $arrayTypes
	 */
	public static function mergeIntoGeneralArray(array $arrayTypes): Type
	{
		$keyTypes = [];
		$valueTypes = [];
		$seenConstantKeys = [];
		foreach ($arrayTypes as $arrayType) {
			$constantArrays = $arrayType->getConstantArrays();
			if ($constantArrays === []) {
				foreach ($arrayType->getArrays() as $type) {
					$keyTypes[] = $type->getIterableKeyType();
					$valueTypes[] = $type->getItemType();
				}
				continue;
			}

			foreach ($constantArrays as $constantArray) {
				$constantValueTypes = $constantArray->getValueTypes();
				foreach ($constantArray->getKeyTypes() as $i => $keyType) {
					$valueTypes[] = $constantValueTypes[$i];

					$key = $keyType->getValue();
					if (array_key_exists($key, $seenConstantKeys)) {
						continue;
					}
					$seenConstantKeys[$key] = true;
					$keyTypes[] = $keyType;
				}
			}
		}

		$templateArrayType = null;
		foreach ($arrayTypes as $arrayType) {
			if (!$arrayType instanceof TemplateArrayType) {
				$templateArrayType = null;
				break;
			}

			$templateArrayType ??= $arrayType;
		}

		$arrayType = new ArrayType(
			TypeCombinator::union(...$keyTypes),
			// merging the arrays into one generalizes them already, and bounds what it merges
			TypeCombinator::union(...ArrayWidening::widen($valueTypes)),
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

		return TypeCombinator::intersect($arrayType, ...self::getCommonAccessoryTypes($arrayTypes));
	}

	/**
	 * The arrays with the shapes among them unioned exactly: each pair of
	 * shapes that ConstantArrayType::tryUnion() merges into one, until no pair
	 * does, and then the shapes with unsealed extras that can take each other in.
	 *
	 * Only shapes whose key sets nest can merge, so the pairs are found by the
	 * keys the shapes share instead of trying every pair: when a shape is
	 * visited, it is tried with the shapes visited before it that have all of
	 * its keys or only keys it has. A merged shape is visited again.
	 *
	 * @param list<Type> $arrayTypes
	 * @return list<Type>
	 */
	public static function reduceShapes(array $arrayTypes): array
	{
		$newArrays = [];
		/** @var array<int, ConstantArrayType> $shapes */
		$shapes = [];
		foreach ($arrayTypes as $arrayType) {
			if (!$arrayType->isConstantArray()->yes()) {
				$newArrays[] = $arrayType;
				continue;
			}
			foreach ($arrayType->getConstantArrays() as $constantArray) {
				$shapes[] = $constantArray;
			}
		}

		// beside the empty array, shapes merge into a shape holding it too
		$hasEmptyArray = false;
		foreach ($shapes as $shape) {
			if ($shape->getKeyTypes() !== [] || $shape->isUnsealed()->yes()) {
				continue;
			}
			$hasEmptyArray = true;
			break;
		}

		// the position of each shape in the result, a merged one taking the
		// earlier position of the two
		$positions = array_keys($shapes);
		// the visited shapes by each of their keys, by their key count, by
		// their required key count and by what values they have (see
		// getShapeHashes())
		$visitedPerKey = [];
		$visitedKeyless = [];
		$visitedKeyCounts = [];
		// the visited shapes by the hash of their keys and values, with each
		// key left out and with none
		$visitedPerHash = [];
		$visitedHashes = [];
		for ($x = 0; $x < count($positions); $x++) {
			if (!array_key_exists($x, $shapes)) {
				continue;
			}
			$shape = $shapes[$x];
			[$keys, $hashes, $queriedHashes, $shapeValues] = self::getShapeHashes($shape);
			$keyCount = count($keys);
			$requiredCount = $keyCount - count($shape->getOptionalKeys());

			// The shapes differing at one key or in their optional keys only,
			// whose hashes with that key left out, or with none, are the same.
			$candidates = [];
			foreach ($queriedHashes as [$kind, $hash]) {
				$candidates += $visitedPerHash[$kind][$hash] ?? [];
			}
			// The shapes with all of the keys of this one or only keys it has,
			// which may hold it or be held by it. Two shapes of the same keys
			// and only single values hold each other only when they have the
			// same values, and are among the shapes above. So are two shapes
			// that are each a single value, having no optional keys either,
			// which merge only when they differ at one key. Of two shapes, the
			// one with more keys holds or merges with the other only when it
			// requires one key at most besides the keys of the other.
			$sharedKeyCounts = [];
			foreach ($keys as $key) {
				foreach ($visitedPerKey[$key] ?? [] as $count => $byRequiredCount) {
					if ($count < $keyCount && $requiredCount > $count + 1) {
						continue;
					}
					foreach ($byRequiredCount as $required => $byValues) {
						if ($count > $keyCount && $required > $keyCount + 1) {
							continue;
						}
						foreach ($byValues as $values => $ys) {
							if (
								($values === self::SINGLE_VALUE_SHAPE && $shapeValues === self::SINGLE_VALUE_SHAPE)
								|| ($values !== self::OTHER_VALUES && $shapeValues !== self::OTHER_VALUES && $count === $keyCount)
							) {
								continue;
							}
							foreach (array_keys($ys) as $y) {
								$sharedKeyCounts[$y] = ($sharedKeyCounts[$y] ?? 0) + 1;
							}
						}
					}
				}
			}
			foreach ($sharedKeyCounts as $y => $sharedKeyCount) {
				if ($sharedKeyCount !== $keyCount && $sharedKeyCount !== $visitedKeyCounts[$y]) {
					continue;
				}
				$candidates[$y] = true;
			}
			$candidates += $keyCount === 0 ? $visitedKeyCounts : $visitedKeyless;
			ksort($candidates);

			foreach (array_keys($candidates) as $y) {
				$merged = $shape->tryUnion($shapes[$y])
					?? ($hasEmptyArray ? ArrayAlgebra::tryUnionConstantArrayBesideEmptyArray($shape, $shapes[$y]) : null);
				if ($merged === null) {
					continue;
				}

				[$yKeys, , , $yValues] = self::getShapeHashes($shapes[$y]);
				$yRequiredCount = count($yKeys) - count($shapes[$y]->getOptionalKeys());
				foreach ($yKeys as $key) {
					unset($visitedPerKey[$key][count($yKeys)][$yRequiredCount][$yValues][$y]);
				}
				foreach ($visitedHashes[$y] as [$kind, $hash]) {
					unset($visitedPerHash[$kind][$hash][$y]);
				}
				unset($shapes[$x], $shapes[$y], $visitedKeyCounts[$y], $visitedKeyless[$y], $visitedHashes[$y]);
				foreach ($merged->getConstantArrays() as $mergedShape) {
					$shapes[] = $mergedShape;
					$positions[] = $positions[$y];
				}
				continue 2;
			}

			$visitedKeyCounts[$x] = $keyCount;
			if ($keyCount === 0) {
				$visitedKeyless[$x] = true;
			}
			foreach ($keys as $key) {
				$visitedPerKey[$key][$keyCount][$requiredCount][$shapeValues][$x] = true;
			}
			foreach ($hashes as [$kind, $hash]) {
				$visitedPerHash[$kind][$hash][$x] = true;
			}
			$visitedHashes[$x] = $hashes;
		}

		// the shapes with unsealed extras, which tryUnion() leaves alone
		$indices = array_keys($shapes);
		$indicesCount = count($indices);
		for ($ii = 0; $ii < $indicesCount - 1; $ii++) {
			$i = $indices[$ii];
			if (!array_key_exists($i, $shapes) || $shapes[$i]->getUnsealedTypes() === null) {
				continue;
			}
			for ($jj = $ii + 1; $jj < $indicesCount; $jj++) {
				$j = $indices[$jj];
				if (
					!array_key_exists($j, $shapes)
					|| $shapes[$j]->getUnsealedTypes() === null
					|| (!$shapes[$i]->isUnsealed()->yes() && !$shapes[$j]->isUnsealed()->yes())
				) {
					continue;
				}
				if ($shapes[$j]->isKeysSupersetOf($shapes[$i])) {
					$shapes[$j] = $shapes[$j]->mergeWith($shapes[$i]);
					$positions[$j] = $positions[$i];
					unset($shapes[$i]);
					continue 2;
				}
				if (!$shapes[$i]->isKeysSupersetOf($shapes[$j])) {
					continue;
				}

				$shapes[$i] = $shapes[$i]->mergeWith($shapes[$j]);
				unset($shapes[$j]);
			}
		}

		$result = [];
		foreach ($shapes as $idx => $shape) {
			// a merge with no known keys left but with unsealed extras is the
			// plain array, as ConstantArrayTypeBuilder::getArray() makes it
			if (count($shape->getKeyTypes()) === 0 && $shape->isUnsealed()->yes()) {
				$unsealed = $shape->getUnsealedTypes();
				if ($unsealed !== null) {
					$result[$positions[$idx]] = new ArrayType($unsealed[0], $unsealed[1]);
					continue;
				}
			}
			$result[$positions[$idx]] = $shape;
		}
		ksort($result);

		return array_merge($newArrays, array_values($result));
	}

	/**
	 * The keys of a shape, the hashes it is found by, the hashes it finds the
	 * shapes differing from it at one key with, and what values it has: one of the
	 * constants above. Equal shapes have equal hashes, whatever their optional keys
	 * and the order of their keys, when their values are single values (see
	 * getSingleValueHash()) or the same objects; other shapes may have equal
	 * hashes too.
	 *
	 * @return array{list<int|string>, list<array{int, int}>, list<array{int, int}>, self::OTHER_VALUES|self::SINGLE_VALUES|self::SINGLE_VALUE_SHAPE}
	 */
	private static function getShapeHashes(ConstantArrayType $shape): array
	{
		$keys = [];
		$keyHashes = [];
		$isAtomic = true;
		$valueTypes = $shape->getValueTypes();
		$all = 0;
		foreach ($shape->getKeyTypes() as $i => $keyType) {
			$key = $keyType->getValue();
			$keys[] = $key;
			$valueHash = self::getSingleValueHash($valueTypes[$i]);
			if ($valueHash === null) {
				$isAtomic = false;
				$valueHash = spl_object_id($valueTypes[$i]);
			}
			// an int key and a string key of the same digits are different keys
			$keyString = (is_int($key) ? "i\0" : "s\0") . $key;
			$keyHash = crc32($keyString . "\0" . $valueHash);
			$keyHashes[] = [$keyHash, crc32($keyString)];
			// summed, not xored: CRC32 is linear in xor, and the xor of the CRCs of
			// the keys of one value would not depend on the value
			$all += $keyHash;
		}

		// a shape is found by its hashes: by all of its keys and values, by
		// all but one of them for the shapes with that key more, and by all
		// but the value at one key for the shapes of the same keys with
		// another value there
		$hashes = [[self::HASH_SHAPE, $all]];
		$queriedHashes = [[self::HASH_SHAPE, $all], [self::HASH_WITH_KEY_MORE, $all]];
		foreach ($keyHashes as [$keyHash, $keyOnlyHash]) {
			$withoutKey = $all - $keyHash;
			$withoutValue = [self::HASH_OTHER_VALUE, $withoutKey + $keyOnlyHash];
			$hashes[] = [self::HASH_WITH_KEY_MORE, $withoutKey];
			$hashes[] = $withoutValue;
			$queriedHashes[] = [self::HASH_SHAPE, $withoutKey];
			$queriedHashes[] = $withoutValue;
		}

		if (!$isAtomic) {
			return [$keys, $hashes, $queriedHashes, self::OTHER_VALUES];
		}

		return [$keys, $hashes, $queriedHashes, $shape->getOptionalKeys() === [] ? self::SINGLE_VALUE_SHAPE : self::SINGLE_VALUES];
	}

	/**
	 * A hash of the one value of a type that has a single value - a constant
	 * scalar, or a shape of such values without optional keys - or null. Two
	 * such types hold each other only when they are equal.
	 */
	private static function getSingleValueHash(Type $type): ?string
	{
		if ($type->isConstantScalarValue()->yes()) {
			$values = $type->getConstantScalarValues();
			if (count($values) !== 1) {
				return null;
			}

			// -0.0 === 0.0
			return serialize($values[0] === 0.0 ? 0.0 : $values[0]);
		}

		$shapes = $type->getConstantArrays();
		if (count($shapes) !== 1 || !$type->isConstantArray()->yes()) {
			return null;
		}
		$shape = $shapes[0];
		if ($shape->getOptionalKeys() !== [] || $shape->isUnsealed()->yes()) {
			return null;
		}

		$hash = '';
		$valueTypes = $shape->getValueTypes();
		foreach ($shape->getKeyTypes() as $i => $keyType) {
			$valueHash = self::getSingleValueHash($valueTypes[$i]);
			if ($valueHash === null) {
				return null;
			}
			$hash .= serialize($keyType->getValue()) . $valueHash;
		}

		return 'a' . crc32($hash);
	}

}
