<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Turbo\TurboExtensionEnabler;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateArrayType;
use PHPStan\Type\Generic\TemplateBenevolentUnionType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateUnionType;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use function array_fill;
use function array_filter;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_merge;
use function array_push;
use function array_slice;
use function array_splice;
use function array_values;
use function count;
use function crc32;
use function get_class;
use function implode;
use function in_array;
use function ksort;
use function serialize;
use function spl_object_id;
use function sprintf;
use function usort;

/**
 * @api
 */
#[ReferencedByTurboExtension(key: 'typeCombinator')]
final class TypeCombinator
{

	/**
	 * Up to this many members of the one union doUnion() flattens, finding out whether two of
	 * them were already compared by scanning it costs less than indexing it.
	 */
	private const ALREADY_NORMALIZED_SCAN_LIMIT = 4;

	/**
	 * Turned on by TurboExtensionEnabler when the native extension is active, so that
	 * union(), intersect() and remove() route through the memoizing TypeCombinatorCache.
	 * Without the extension the operations run exactly as they always have — the flag
	 * costs a property read, and nothing else changes.
	 */
	private static ?bool $cacheEnabled = null;

	/**
	 * Memoization hands back shared Type instances, and a Type lazily resolves a
	 * ClassReflection belonging to the container that created it — so the cache must
	 * not outlive its container.
	 */
	public static function clearCache(): void
	{
		if ((self::$cacheEnabled ??= TurboExtensionEnabler::isTypeCombinatorCacheEnabled()) === false) {
			return;
		}

		TypeCombinatorCache::clearCache();
	}

	public static function addNull(Type $type): Type
	{
		// asking the type itself is both cheaper than NullType::isSuperTypeOf()
		// (UnionType memoizes isNull(), isSubTypeOf() recomputes) and right for
		// `never`, of which null is a supertype without never containing it.
		if ($type->isNull()->no()) {
			return self::union($type, new NullType());
		}

		return $type;
	}

	public static function remove(Type $fromType, Type $typeToRemove): Type
	{
		if (self::$cacheEnabled ??= TurboExtensionEnabler::isTypeCombinatorCacheEnabled()) {
			return TypeCombinatorCache::remove($fromType, $typeToRemove);
		}

		return self::doRemove($fromType, $typeToRemove);
	}

	/**
	 * Whether removing the union's members all at once gives what removing them
	 * one at a time would: true when the subtraction is a set difference, so no
	 * order of removals can sharpen the result.
	 *
	 * Everywhere else the members have to come off one at a time - removing one
	 * can narrow the type so that removing the next achieves more.
	 */
	private static function canRemoveUnionAtOnce(Type $fromType): bool
	{
		// A sealed hierarchy - an enum, or a hierarchy an
		// AllowedSubTypesClassReflectionExtension seals - subtracts by dropping
		// allowed subtypes, and which ones are dropped does not depend on order.
		$classReflections = $fromType->getObjectClassReflections();
		if (count($classReflections) === 1 && $classReflections[0]->getAllowedSubTypes() !== null) {
			return true;
		}

		// A union removes the whole thing from each of its members, and each of
		// those removals weighs this same question again - so a member that needs
		// the removals one at a time still gets them that way.
		return $fromType instanceof UnionType;
	}

	/** @internal Delegated to from TypeCombinatorCache, which the native extension shadows to memoize it. */
	public static function doRemove(Type $fromType, Type $typeToRemove): Type
	{
		if ($typeToRemove instanceof UnionType) {
			// Removing the members one at a time is what keeps the result precise:
			// removing one can narrow the type so that removing the next achieves
			// more. A sealed hierarchy is the exception - there the subtraction is
			// a set difference over the allowed subtypes, so the order the members
			// come off cannot matter, and peeling them would rebuild the whole
			// subtraction once per member (removing an enum from its own type then
			// costs O(cases^2)).
			if (self::canRemoveUnionAtOnce($fromType)) {
				$removed = $fromType->tryRemove($typeToRemove);
				if ($removed !== null) {
					return $removed;
				}

				// a union removed the whole union from each of its members and none
				// of them changed - removing the members of it one at a time cannot
				// change one either, peeling would only repeat that work
				if ($fromType instanceof UnionType) {
					return $fromType;
				}
			}

			// mixed is the top type, so removing a union from it is exactly
			// subtracting the members it does not subtract already, all of them at
			// once. Peeling them one at a time would ask each member about the mixed
			// the earlier members left, which walks everything they subtracted.
			if ($fromType instanceof MixedType && !$fromType instanceof TemplateType) {
				$subtractedType = $fromType->getSubtractedType();
				$remainingTypes = [];
				foreach ($typeToRemove->getTypes() as $unionTypeToRemove) {
					if ($subtractedType !== null && $subtractedType->isSuperTypeOf($unionTypeToRemove)->yes()) {
						continue;
					}
					$remainingTypes[] = $unionTypeToRemove;
				}

				if ($remainingTypes === []) {
					return $fromType;
				}

				return $fromType->subtract(count($remainingTypes) === 1 ? $remainingTypes[0] : new UnionType($remainingTypes));
			}

			// a value none of the union's keyed members stands for overlaps none
			// of them - one lookup instead of a removal per member
			$fromTypeKey = FiniteTypeSet::key($fromType);
			if ($fromTypeKey !== null) {
				$finiteTypeSet = $typeToRemove->getFiniteTypeSet();
				if ($finiteTypeSet !== null && $finiteTypeSet->isComplete() && !$finiteTypeSet->has($fromTypeKey)) {
					return $fromType;
				}
			}

			foreach ($typeToRemove->getTypes() as $unionTypeToRemove) {
				$fromType = self::remove($fromType, $unionTypeToRemove);
				if ($fromType instanceof NeverType) {
					// there is nothing left to remove from
					break;
				}
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
				if ($innerType->isNull()->yes()) {
					return true;
				}
			}

			return false;
		}

		return $type instanceof NullType;
	}

	/**
	 * The type, generalized where it holds more constant array values than
	 * ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT: shapes with the same keys
	 * merged, shapes sharing a required key folded into one, and what is still
	 * too big degraded to an oversized array.
	 *
	 * union() is exact and never does this. Call it where the analysis joins
	 * values that can grow without bound, such as the types of a variable after
	 * branches, so a join stays small enough to compute with.
	 */
	public static function widen(Type $type): Type
	{
		$types = $type instanceof UnionType && !$type instanceof TemplateType ? $type->getTypes() : [$type];
		$arrayTypes = [];
		$otherTypes = [];
		foreach ($types as $member) {
			if ($member->isArray()->yes() && !$member instanceof LateResolvableType) {
				$arrayTypes[] = $member;
				continue;
			}
			$otherTypes[] = $member;
		}
		if ($arrayTypes === []) {
			return $type;
		}

		if (self::countConstantArrayValueTypes($arrayTypes) <= ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
			return $type;
		}

		// as union() merges them, with the shapes generalized before their
		// common accessory types go back on
		$widenedArrayTypes = self::processArrayTypes($arrayTypes, true);

		$widened = self::union(...$otherTypes, ...$widenedArrayTypes);
		if ($type instanceof BenevolentUnionType) {
			return TypeUtils::toBenevolentUnion($widened);
		}

		return $widened;
	}

	public static function union(Type ...$types): Type
	{
		if (self::$cacheEnabled ??= TurboExtensionEnabler::isTypeCombinatorCacheEnabled()) {
			return TypeCombinatorCache::union(...$types);
		}

		return self::doUnion(...$types);
	}

	/** @internal Delegated to from TypeCombinatorCache, which the native extension shadows to memoize it. */
	public static function doUnion(Type ...$types): Type
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

			// union(X, X) = X — for equal array operands, not just identical
			// ones. For arrays the general path below is not a no-op on a pair
			// that already denotes a single type: it rebuilds them from
			// ArrayType::getIterableKeyType(), which coerces a subtracted key
			// type down to the plain array-key union, so `array<mixed~'User',
			// mixed>` unioned with an equal instance came back as plain
			// `array`. Whether the two operands happen to be the same object
			// must not decide the result.
			//
			// Non-array operands keep taking the general path. It is lossless
			// for them, and it has to stay in charge because `equals()` is
			// weaker than interchangeability for some types: a benevolent
			// `(int|string)` equals a plain `int|string` and `Foo=final` equals
			// `Foo`, and there the general path deliberately yields the wider
			// operand rather than whichever one was passed first.
			if ($a === $b || ($a->equals($b) && $a->isArray()->yes())) {
				return $a;
			}
		}

		// A member passed more than once contributes nothing; dropping the
		// repeats up front keeps the pairwise comparison below from paying for
		// them (array_values() of a shape unions the same value type per slot).
		if ($typesCount > 2) {
			$seenTypes = [];
			$uniqueTypes = [];
			foreach ($types as $type) {
				$typeId = spl_object_id($type);
				if (isset($seenTypes[$typeId])) {
					continue;
				}
				$seenTypes[$typeId] = true;
				$uniqueTypes[] = $type;
			}
			if (count($uniqueTypes) === 1) {
				return $uniqueTypes[0];
			}
			if (count($uniqueTypes) === 2) {
				return self::union($uniqueTypes[0], $uniqueTypes[1]);
			}
			$types = $uniqueTypes;
		}

		$alreadyNormalized = [];
		$alreadyNormalizedCounter = 0;

		$benevolentTypes = [];
		$neverCount = 0;
		$markers = [];
		// transform A | (B | C) to A | B | C - in one pass, a union's members are
		// never unions, implicit never or implicit mixed themselves
		$flattenedTypes = [];
		foreach ($types as $type) {
			if (
				$type instanceof MixedType
				&& !$type->isExplicitMixed()
				&& !$type instanceof TemplateMixedType
				&& $type->getSubtractedType() === null
			) {
				return $type;
			}
			if ($type instanceof UnresolvedTemplateArgumentType && !$type->isClosureSignature()) {
				$markers = self::addMarker($markers, $type);
				continue;
			}
			if ($type instanceof NeverType && !$type->isExplicit()) {
				$neverCount++;
				$flattenedTypes[] = $type;
				continue;
			}
			if ($type instanceof BenevolentUnionType) {
				if ($type instanceof TemplateType) {
					$flattenedTypes[] = $type;
					continue;
				}
				foreach ($type->getTypes() as $benevolentInnerType) {
					$benevolentTypes[spl_object_id($benevolentInnerType)] = $benevolentInnerType;
					$flattenedTypes[] = $benevolentInnerType;
				}
				continue;
			}
			if (!($type instanceof UnionType) || $type instanceof TemplateType) {
				$flattenedTypes[] = $type;
				continue;
			}

			$typesInner = $type->getTypes();
			$alreadyNormalized[$alreadyNormalizedCounter] = $typesInner;
			$alreadyNormalizedCounter++;
			foreach ($typesInner as $innerType) {
				if ($innerType instanceof UnresolvedTemplateArgumentType && !$innerType->isClosureSignature()) {
					$markers = self::addMarker($markers, $innerType);
					continue;
				}
				$flattenedTypes[] = $innerType;
			}
		}
		if ($markers !== []) {
			return self::unionWithMarkers(self::union(...$flattenedTypes), $markers);
		}
		$types = $flattenedTypes;
		$typesCount = count($types);

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
		$hasGenericBooleanType = false;
		$enumCaseTypes = [];
		$numberTypes = [];
		$stringRefinements = [];
		// the merged numbers and strings take the place of the first of them: of two
		// members each holding the other, the pairwise comparison keeps the later one
		$firstNumberIndex = null;
		$firstStringIndex = null;
		for ($i = 0; $i < $typesCount; $i++) {
			// ints, floats and the refinements of string are merged kind by kind
			if ($types[$i]->toExactNumberSet() !== null) {
				$firstNumberIndex ??= $i;
				$numberTypes[] = $types[$i];
				unset($types[$i]);
				continue;
			}
			if ($types[$i]->getStringRefinement() !== null) {
				$firstStringIndex ??= $i;
				$stringRefinements[] = $types[$i];
				unset($types[$i]);
				continue;
			}

			if ($types[$i]->isConstantScalarValue()->yes()) {
				$type = $types[$i];
				$scalarTypes[get_class($type)][$type->describe(VerbosityLevel::cache())] = $type;
				unset($types[$i]);
				continue;
			}

			// only a type containing every value of bool (not T of bool) absorbs both constants
			if ($types[$i]->isBoolean()->yes()) {
				$hasGenericBooleanType = $hasGenericBooleanType || $types[$i]->isSuperTypeOf(new BooleanType())->yes();
			} else {
				$enumCase = $types[$i]->getEnumCaseObject();
				if ($enumCase !== null) {
					$enumCaseTypes[$types[$i]->describe(VerbosityLevel::cache())] = $types[$i];

					unset($types[$i]);
					continue;
				}
			}

			// a late-resolvable type, such as a conditional type, stays as it is until it resolves:
			// merged with the other arrays, it would lose its condition and its accessory types
			if (!$types[$i]->isArray()->yes() || $types[$i] instanceof LateResolvableType) {
				continue;
			}

			$arrayTypes[] = $types[$i];
			unset($types[$i]);
		}

		$enumCaseTypes = array_values($enumCaseTypes);
		// before the strings are merged, which leaves their constants unkeyed
		if (isset($scalarTypes[IntersectionType::class])) {
			$scalarTypes[IntersectionType::class] = self::reduceRefinedConstantScalars($scalarTypes[IntersectionType::class], $scalarTypes);
		}
		[$numberRanges, $numberConstants] = ScalarUnion::unionNumbers($numberTypes);
		[$stringTypes, $constantStrings] = ScalarUnion::unionStrings($stringRefinements, array_values($scalarTypes[ConstantStringType::class] ?? []));
		if ($constantStrings === []) {
			unset($scalarTypes[ConstantStringType::class]);
		} else {
			$scalarTypes[ConstantStringType::class] = $constantStrings;
		}
		$orderedTypes = [];
		for ($i = 0; $i < $typesCount; $i++) {
			if (isset($types[$i])) {
				$orderedTypes[] = $types[$i];
			} elseif ($i === $firstNumberIndex) {
				array_push($orderedTypes, ...$numberRanges);
			} elseif ($i === $firstStringIndex) {
				array_push($orderedTypes, ...$stringTypes);
			}
		}
		$types = $orderedTypes;
		$typesCount = count($types);

		if ($numberConstants !== []) {
			$scalarTypes[ConstantIntegerType::class] = $numberConstants;
		}
		foreach ($scalarTypes as $classType => $scalarTypeItems) {
			if ($classType === ConstantBooleanType::class && $hasGenericBooleanType) {
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

		// Exact absorption first: an array another member holds, such as
		// array<int> in iterable<int>, is dropped before processArrayTypes()
		// widens the arrays into one another, which would lose the rest of them.
		if ($arrayTypes !== [] && $types !== []) {
			$remainingArrayTypes = [];
			foreach ($arrayTypes as $arrayType) {
				foreach ($types as $type) {
					if (
						!TypeKind::areDisjoint($type->getKindMask(), $arrayType->getKindMask())
						&& $type->isSuperTypeOf($arrayType)->yes()
					) {
						continue 2;
					}
				}
				$remainingArrayTypes[] = $arrayType;
			}
			$arrayTypes = $remainingArrayTypes;
		}

		// compareTypesInUnion() leaves a pair of constant arrays as it is unless
		// both are intersections, so such pairs are skipped without calling it. A
		// bare ConstantArrayType is the only type whose getConstantArrays() is
		// itself. Every array went to processArrayTypes(), so only its results
		// can be such.
		$processedArrayTypes = self::processArrayTypes($arrayTypes);
		$isPlainConstantArray = null;
		if (count($processedArrayTypes) > 1) {
			$isPlainConstantArray = array_fill(0, count($types), false);
			foreach ($processedArrayTypes as $processedArrayType) {
				$isPlainConstantArray[] = $processedArrayType->getConstantArrays() === [$processedArrayType];
			}
		}
		$types = array_merge(
			$types,
			$processedArrayTypes,
		);
		$typesCount = count($types);

		// $alreadyNormalized keeps the members alive, so their object ids stay
		// unique while this lookup refers to them
		$normalizedGroupsByType = [];
		$normalizedTwice = [];
		// a single flattened union of a few members, as in most unions, is cheaper to scan - without
		// new local variables, which every call of this method initializes
		$scanAlreadyNormalized = $alreadyNormalizedCounter === 1 && count($alreadyNormalized[0]) <= self::ALREADY_NORMALIZED_SCAN_LIMIT;
		if ($typesCount > 1 && !$scanAlreadyNormalized) {
			foreach ($alreadyNormalized as $group => $normalizedTypes) {
				foreach ($normalizedTypes as $normalizedType) {
					$normalizedTypeId = spl_object_id($normalizedType);
					if (isset($normalizedGroupsByType[$normalizedTypeId][$group])) {
						$normalizedTwice[$normalizedTypeId] = true;
						continue;
					}
					$normalizedGroupsByType[$normalizedTypeId][$group] = $group;
				}
			}
		}

		// transform A | A to A
		// transform A | never to A
		for ($i = 0; $i < $typesCount; $i++) {
			if ($scanAlreadyNormalized) {
				// null for a type not in the flattened union, which skips the scan of its pairs below
				$iGroups = in_array($types[$i], $alreadyNormalized[0], true) ? [] : null;
			} else {
				$iGroups = $normalizedGroupsByType[spl_object_id($types[$i])] ?? null;
			}
			for ($j = $i + 1; $j < $typesCount; $j++) {
				if ($isPlainConstantArray !== null && $isPlainConstantArray[$i] && $isPlainConstantArray[$j]) {
					continue;
				}
				if ($iGroups !== null) {
					if ($scanAlreadyNormalized) {
						// what isAlreadyNormalized() answers: $types[$j] is another member of
						// the flattened union, which holds $types[$i]
						$normalizedTypes = $alreadyNormalized[0];
						if (
							$types[$i] !== $types[$j]
								? in_array($types[$j], $normalizedTypes, true)
								: count(array_keys($normalizedTypes, $types[$j], true)) > 1
						) {
							continue;
						}
					} elseif (self::isAlreadyNormalized($iGroups, $normalizedGroupsByType, $normalizedTwice, $types[$i], $types[$j])) {
						continue;
					}
				}
				$compareResult = self::compareTypesInUnion($types[$i], $types[$j]);
				if ($compareResult === null) {
					continue;
				}

				[$a, $b] = $compareResult;
				if ($a !== null) {
					$types[$i] = $a;
					$iGroups = $scanAlreadyNormalized
						? (in_array($a, $alreadyNormalized[0], true) ? [] : null)
						: ($normalizedGroupsByType[spl_object_id($a)] ?? null);
					if ($isPlainConstantArray !== null) {
						$isPlainConstantArray[$i] = $a->getConstantArrays() === [$a];
						array_splice($isPlainConstantArray, $j, 1);
					}
					array_splice($types, $j--, 1);
					$typesCount--;
					continue 1;
				}
				if ($b === null) {
					continue;
				}

				$types[$j] = $b;
				if ($isPlainConstantArray !== null) {
					$isPlainConstantArray[$j] = $b->getConstantArrays() === [$b];
					array_splice($isPlainConstantArray, $i, 1);
				}
				array_splice($types, $i--, 1);
				$typesCount--;
				continue 2;
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
			$allBenevolent = true;
			foreach ($types as $type) {
				// the input types keep the benevolent members alive, so their ids stay unique
				if (isset($benevolentTypes[spl_object_id($type)])) {
					continue;
				}

				// a member that absorbed a benevolent member stays benevolent,
				// like int absorbing int<1, max> from (int<1, max>|string) and 0
				foreach ($benevolentTypes as $benevolentType) {
					if ($type->isSuperTypeOf($benevolentType)->yes()) {
						continue 2;
					}
				}

				$allBenevolent = false;
				break;
			}

			if ($allBenevolent) {
				return new BenevolentUnionType(array_values($types), true);
			}
		}

		return new UnionType(array_values($types), true);
	}

	/**
	 * An unresolved template argument is a variable of its site, not the type it
	 * currently stands for: unresolved(int) from two different sites are two
	 * variables, and neither int nor the other variable absorbs it. Only the same
	 * variable merges - into the wider of the two inferred types.
	 *
	 * @param array<string, UnresolvedTemplateArgumentType> $markers
	 * @return array<string, UnresolvedTemplateArgumentType>
	 */
	private static function addMarker(array $markers, UnresolvedTemplateArgumentType $marker): array
	{
		$key = spl_object_id($marker->getSite()) . '#' . $marker->getTemplateName();
		$existing = $markers[$key] ?? null;
		if ($existing === null || $existing === $marker) {
			$markers[$key] = $marker;
			return $markers;
		}

		$existingInitial = $existing->getInitialType();
		$initial = $marker->getInitialType();
		if ($existingInitial === null || $initial === null) {
			// nothing inferred stands for the template's default or bound
			$markers[$key] = $existingInitial === null ? $existing : $marker;
			return $markers;
		}

		$markers[$key] = $existing->withInitialType(self::union($existingInitial, $initial));

		return $markers;
	}

	/**
	 * @param array<string, UnresolvedTemplateArgumentType> $markers
	 */
	private static function unionWithMarkers(Type $rest, array $markers): Type
	{
		if ($rest instanceof MixedType && !$rest instanceof TemplateMixedType && $rest->getSubtractedType() === null) {
			return $rest;
		}

		if ($rest instanceof NeverType) {
			$members = [];
		} elseif ($rest instanceof UnionType && !$rest instanceof TemplateType) {
			$members = $rest->getTypes();
		} else {
			$members = [$rest];
		}
		foreach ($markers as $marker) {
			$members[] = $marker;
		}
		if (count($members) === 1) {
			return $members[0];
		}
		if ($rest instanceof BenevolentUnionType && !$rest instanceof TemplateType) {
			return new BenevolentUnionType($members, true);
		}

		return new UnionType($members, true);
	}

	/**
	 * Whether $a and $b are members of one union that was flattened into this
	 * one - such members were already compared against each other.
	 *
	 * @param array<int, int> $aGroups the groups of $a in $normalizedGroupsByType
	 * @param array<int, array<int, int>> $normalizedGroupsByType member object id => (index of a flattened union holding it => the same index)
	 * @param array<int, true> $normalizedTwice member object ids a flattened union held twice
	 */
	private static function isAlreadyNormalized(array $aGroups, array $normalizedGroupsByType, array $normalizedTwice, Type $a, Type $b): bool
	{
		if ($a === $b) {
			return isset($normalizedTwice[spl_object_id($a)]);
		}

		$bGroups = $normalizedGroupsByType[spl_object_id($b)] ?? null;
		if ($bGroups === null) {
			return false;
		}

		foreach ($aGroups as $group) {
			if (isset($bGroups[$group])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Scalar buckets are compared only against the non-scalar union members, which
	 * is correct for plain constants (distinct values are disjoint) but not for
	 * constants refined by another type ('Foo'&hasMethod(bar)). Those are absorbed
	 * here by the plain constant of the same value, or by a less refined
	 * intersection of the same value. Values never need to be compared across.
	 *
	 * @param array<string, Type> $intersections
	 * @param array<string, array<string, Type>> $scalarTypes
	 * @return array<string, Type>
	 */
	private static function reduceRefinedConstantScalars(array $intersections, array $scalarTypes): array
	{
		$byValue = [];
		foreach ($intersections as $key => $intersection) {
			$constants = $intersection->getConstantScalarTypes();
			if (count($constants) !== 1) {
				continue;
			}
			$valueKey = $constants[0]->describe(VerbosityLevel::cache());
			$plain = $scalarTypes[get_class($constants[0])][$valueKey] ?? null;
			if ($plain !== null && $plain->isSuperTypeOf($intersection)->yes()) {
				unset($intersections[$key]);
				continue;
			}
			$byValue[get_class($constants[0])][$valueKey][] = $key;
		}

		foreach ($byValue as $valueKeys) {
			foreach ($valueKeys as $keys) {
				$keysCount = count($keys);
				for ($i = 0; $i < $keysCount; $i++) {
					for ($j = 0; $j < $keysCount; $j++) {
						if ($i === $j || !isset($intersections[$keys[$i]], $intersections[$keys[$j]])) {
							continue;
						}
						if (!$intersections[$keys[$i]]->isSuperTypeOf($intersections[$keys[$j]])->yes()) {
							continue;
						}
						unset($intersections[$keys[$j]]);
					}
				}
			}
		}

		return $intersections;
	}

	/**
	 * @return array{Type, null}|array{null, Type}|null
	 */
	private static function compareTypesInUnion(Type $a, Type $b): ?array
	{
		// values of different kinds never merge into one member
		if (TypeKind::areDisjoint($a->getKindMask(), $b->getKindMask())) {
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

		$union = $a->tryUnion($b);
		if ($union !== null) {
			return [$union, null];
		}

		$union = $b->tryUnion($a);
		if ($union !== null) {
			return [null, $union];
		}

		if ($b->isSuperTypeOf($a)->yes()) {
			return [null, $b];
		}

		if ($a->isSuperTypeOf($b)->yes()) {
			return [$a, null];
		}

		return null;
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
	 * @param list<Type> $arrayTypes
	 * @return Type[]
	 */
	private static function processArrayTypes(array $arrayTypes, bool $widen = false): array
	{
		if ($arrayTypes === []) {
			return [];
		}

		$emptyArray = self::findEmptyArrayNextToOversizedArray($arrayTypes);
		if ($emptyArray !== null) {
			// like reduceArrays() does when it generalizes to an oversized array:
			// folded into it, the empty array would be lost to the oversized
			// accessory, which accepts only arrays that hold something
			$otherArrayTypes = [];
			foreach ($arrayTypes as $arrayType) {
				if ($arrayType->isConstantArray()->yes() && $arrayType->isIterableAtLeastOnce()->no()) {
					continue;
				}
				$otherArrayTypes[] = $arrayType;
			}

			return [$emptyArray, ...self::processArrayTypes($otherArrayTypes, $widen)];
		}

		$accessoryTypes = self::processArrayAccessoryTypes($arrayTypes);

		if (count($arrayTypes) === 1 && !$widen) {
			return [
				self::intersect(...$arrayTypes, ...$accessoryTypes),
			];
		}

		$keyTypesForGeneralArray = [];
		$valueTypesForGeneralArray = [];
		$generalArrayOccurred = false;
		$seenConstantKeyTypes = [];

		foreach ($arrayTypes as $arrayType) {
			$constantArrays = $arrayType->getConstantArrays();
			$isConstantArray = $constantArrays !== [];

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
					if (array_key_exists($keyTypeValue, $seenConstantKeyTypes)) {
						continue;
					}
					$seenConstantKeyTypes[$keyTypeValue] = true;
					$keyTypesForGeneralArray[] = $keyType;
				}
			}
		}

		if ($generalArrayOccurred) {
			if (count($arrayTypes) === 1) {
				return [self::intersect($arrayTypes[0], ...$accessoryTypes)];
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
				// merging the arrays into one generalizes them already, and bounds what it merges
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

		if ($widen) {
			// generalized first: unioning the shapes exactly would be wasted on
			// the shapes the generalization folds
			$shapes = [];
			foreach ($arrayTypes as $arrayType) {
				if (!$arrayType->isConstantArray()->yes()) {
					$shapes[] = $arrayType;
					continue;
				}
				foreach ($arrayType->getConstantArrays() as $constantArray) {
					$shapes[] = $constantArray;
				}
			}
			$reducedArrayTypes = self::reduceArrays(array_values(self::optimizeConstantArrays($shapes)));
		} else {
			$reducedArrayTypes = self::reduceArrays($arrayTypes);
		}
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
	 * Merges the arrays like a chain of mergeWith() calls. They have the same
	 * keys in the same order, so the chain unions the values at each key. At a
	 * key where every array holds only constant scalars, one union of all the
	 * values is the same as the chain of unions, which was quadratic in the size
	 * of its growing result - the chain only merges the rest of the shape, with a
	 * placeholder at that key.
	 *
	 * @param non-empty-list<ConstantArrayType> $arrays
	 */
	private static function mergeConstantArraysWithSameKeys(array $arrays): ConstantArrayType
	{
		$constantScalarValueTypes = [];
		if (!$arrays[0] instanceof TemplateType) {
			foreach (array_keys($arrays[0]->getValueTypes()) as $i) {
				$constantScalarValueTypes[$i] = [];
			}
		}
		foreach ($arrays as $array) {
			foreach ($array->getValueTypes() as $i => $valueType) {
				if (!array_key_exists($i, $constantScalarValueTypes)) {
					continue;
				}
				// the classes whose union is a plain concatenation of their
				// members, deduplicated - not ones merely holding a constant scalar
				$members = $valueType instanceof UnionType && !$valueType instanceof BenevolentUnionType && !$valueType instanceof TemplateType
					? $valueType->getTypes()
					: [$valueType];
				foreach ($members as $member) {
					if (!$member instanceof ConstantScalarType || $member instanceof TemplateType) { // @phpstan-ignore phpstanApi.instanceofType
						unset($constantScalarValueTypes[$i]);
						continue 2;
					}
				}
				$constantScalarValueTypes[$i][] = $valueType;
			}
		}

		$placeholder = new NeverType(true);
		$merged = null;
		foreach ($arrays as $array) {
			if ($constantScalarValueTypes !== []) {
				$valueTypes = $array->getValueTypes();
				foreach (array_keys($constantScalarValueTypes) as $i) {
					$valueTypes[$i] = $placeholder;
				}
				$array = new ConstantArrayType($array->getKeyTypes(), $valueTypes, $array->getNextAutoIndexes(), $array->getOptionalKeys(), $array->isList(), $array->getUnsealedTypes());
			}
			$merged = $merged === null ? $array : $merged->mergeWith($array);
		}

		if ($constantScalarValueTypes === []) {
			return $merged;
		}

		$valueTypes = $merged->getValueTypes();
		foreach ($constantScalarValueTypes as $i => $types) {
			$valueTypes[$i] = self::union(...$types);
		}

		return new ConstantArrayType($merged->getKeyTypes(), $valueTypes, $merged->getNextAutoIndexes(), $merged->getOptionalKeys(), $merged->isList(), $merged->getUnsealedTypes());
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
				$collapsed[] = self::widenValueTypes(self::mergeConstantArraysWithSameKeys($group));
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

		// Stage 2: fold the non-empty shapes with different key sets into one
		// shape, the keys missing from some of them becoming optional - as
		// loop generalization folds them. Which keys go together is lost, but
		// unlike the generalization below, every key keeps its own value type.
		// Only shapes sharing a required key fold: they are variants of one
		// record. Shapes without one, such as lookup maps of disjoint keys,
		// would fold into a shape of all their keys, too big to keep inside
		// the shape enclosing them.
		$merged = null;
		$typesWithMerged = [];
		foreach ($types as $type) {
			if (!$type instanceof ConstantArrayType || !$type->isIterableAtLeastOnce()->yes()) {
				$typesWithMerged[] = $type;
				continue;
			}
			if ($merged === null) {
				$merged = $type;
				continue;
			}
			if ($merged->getUnsealedTypes() === null || $type->getUnsealedTypes() === null) {
				// the legacy merge keeps only the keys of $merged - add the
				// keys only $type has to it first, as optional keys
				$mergedKeys = [];
				foreach ($merged->getKeyTypes() as $keyType) {
					$mergedKeys[$keyType->getValue()] = true;
				}
				$builder = null;
				foreach ($type->getKeyTypes() as $i => $keyType) {
					if (array_key_exists($keyType->getValue(), $mergedKeys)) {
						continue;
					}
					$builder ??= ConstantArrayTypeBuilder::createFromConstantArray($merged);
					$builder->setOffsetValueType($keyType, $type->getValueTypes()[$i], true);
				}
				if ($builder !== null) {
					$extended = $builder->getArray();
					if (!$extended instanceof ConstantArrayType) {
						$merged = null;
						break;
					}
					$merged = $extended;
				}
			}
			$merged = $merged->mergeWith($type);
		}
		if ($merged !== null) {
			$merged = self::widenValueTypes($merged);
		}
		if (
			$merged !== null
			&& count($typesWithMerged) + 1 < count($types)
			&& count($merged->getOptionalKeys()) < count($merged->getKeyTypes())
		) {
			$typesWithMerged[] = $merged;
			if (self::countConstantArrayValueTypes($typesWithMerged) <= ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
				return $typesWithMerged;
			}
		}

		$results = [];
		$emptyArrays = [];
		$eachIsOversized = true;
		foreach ($types as $type) {
			if ($type->isConstantArray()->yes() && $type->isIterableAtLeastOnce()->no()) {
				$emptyArrays[] = $type;
				continue;
			}

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
				$valueType = self::widen(TypeCombinator::union(...array_values($valueTypes)));

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

		if ($eachIsOversized && $results !== []) {
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
			$valueType = self::widen(self::union(...$valueTypes));

			if ($valueType instanceof UnionType && count($valueType->getTypes()) > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
				$valueType = $valueType->generalize(GeneralizePrecision::lessSpecific());
			}

			$accessories = [];
			if ($eachIsList) {
				$accessories[] = new AccessoryArrayListType();
			}
			$accessories[] = new NonEmptyArrayType();
			$accessories[] = new OversizedArrayType();

			return [...$emptyArrays, self::intersect(new ArrayType($keyType, $valueType), ...$accessories)];
		}

		return [...$emptyArrays, ...$results];
	}

	/**
	 * The shape with its value types widened: merging shapes unions the values
	 * at each key, and those joins grow like any other.
	 */
	private static function widenValueTypes(ConstantArrayType $array): ConstantArrayType
	{
		$valueTypes = $array->getValueTypes();
		$changed = false;
		foreach ($valueTypes as $i => $valueType) {
			$widened = self::widen($valueType);
			if ($widened === $valueType) {
				continue;
			}
			$valueTypes[$i] = $widened;
			$changed = true;
		}
		if (!$changed) {
			return $array;
		}

		return new ConstantArrayType($array->getKeyTypes(), $valueTypes, $array->getNextAutoIndexes(), $array->getOptionalKeys(), $array->isList(), $array->getUnsealedTypes());
	}

	/**
	 * @param Type[] $types
	 */
	public static function countConstantArrayValueTypes(array $types): int
	{
		$constantArrayValuesCount = 0;
		// A type shared by several positions (array shape keys written
		// through a union offset all hold the same value type) is counted
		// once per position, but traversed only once. The entries keep the
		// visited types alive so that their object ids are not reused.
		$countsByType = [];
		foreach ($types as $type) {
			TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$constantArrayValuesCount, &$countsByType): Type {
				$id = spl_object_id($type);
				if (array_key_exists($id, $countsByType)) {
					$constantArrayValuesCount += $countsByType[$id][1];
					return $type;
				}

				$countBefore = $constantArrayValuesCount;
				if ($type instanceof ConstantArrayType) {
					$constantArrayValuesCount += count($type->getValueTypes());
				}

				$result = $traverse($type);
				$countsByType[$id] = [$type, $constantArrayValuesCount - $countBefore];

				return $result;
			});
		}
		return $constantArrayValuesCount;
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
	private static function reduceArrays(array $arrayTypes): array
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
		// the visited shapes by their key count, by whether each of their
		// values is a single constant scalar, and by each of their keys
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
			[$keys, $hashes, $isAtomic] = self::getShapeHashes($shape);
			$keyCount = count($keys);

			// The shapes differing at one key or in their optional keys only,
			// whose hashes with that key left out, or with none, are the same.
			$candidates = [];
			foreach ($hashes as $hash) {
				$candidates += $visitedPerHash[$hash] ?? [];
			}
			// The shapes with all of the keys of this one or only keys it has,
			// which may hold it or be held by it. Two shapes of the same keys
			// and only single constant scalar values hold each other only when
			// they have the same values, and are among the shapes above.
			$sharedKeyCounts = [];
			foreach ($keys as $key) {
				foreach ($visitedPerKey[$key] ?? [] as $count => $byAtomic) {
					foreach ($byAtomic as $atomic => $ys) {
						if ($isAtomic && $atomic === 1 && $count === $keyCount) {
							continue;
						}
						foreach (array_keys($ys) as $y) {
							$sharedKeyCounts[$y] = ($sharedKeyCounts[$y] ?? 0) + 1;
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

				[$yKeys, , $yIsAtomic] = self::getShapeHashes($shapes[$y]);
				foreach ($yKeys as $key) {
					unset($visitedPerKey[$key][count($yKeys)][(int) $yIsAtomic][$y]);
				}
				foreach ($visitedHashes[$y] as $hash) {
					unset($visitedPerHash[$hash][$y]);
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
				$visitedPerKey[$key][$keyCount][(int) $isAtomic][$x] = true;
			}
			foreach ($hashes as $hash) {
				$visitedPerHash[$hash][$x] = true;
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
	 * The keys of a shape, the hashes of its keys and values - over all of them
	 * and with each key left out - and whether each of its values is a single
	 * value (see getSingleValueHash()). Equal shapes have equal hashes,
	 * whatever their optional keys and the order of their keys, when their
	 * values are single values or the same objects; other shapes may have
	 * equal hashes too.
	 *
	 * @return array{list<int|string>, list<string>, bool}
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
			$keyHash = crc32(serialize($key) . "\0" . $valueHash);
			$keyHashes[] = $keyHash;
			$all ^= $keyHash;
		}

		$count = count($keys);
		$hashes = [$count . ':' . $all];
		foreach ($keyHashes as $keyHash) {
			$hashes[] = ($count - 1) . ':' . ($all ^ $keyHash);
		}

		return [$keys, $hashes, $isAtomic];
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

	/**
	 * Fast path for intersect(): the intersection of two unions whose members are all
	 * finite, mutually-disjoint values (constant scalars and/or enum cases) is their
	 * identity-keyed set intersection. Returns null when either union has a member that is
	 * not such a value, in which case the caller falls back to the general A & (B|C)
	 * distribution.
	 */
	/** @phpstan-assert-if-true UnionType $type */
	private static function isPlainOrBenevolentUnion(Type $type): bool
	{
		$class = get_class($type);

		return $class === UnionType::class || $class === BenevolentUnionType::class;
	}

	private static function intersectFiniteUnions(UnionType $a, UnionType $b): ?Type
	{
		$membersA = self::finiteUnionMembers($a);
		if ($membersA === null) {
			return null;
		}

		$membersB = self::finiteUnionMembers($b);
		if ($membersB === null) {
			return null;
		}

		$common = [];
		foreach ($membersA as $key => $member) {
			if (!array_key_exists($key, $membersB)) {
				continue;
			}

			$common[] = $member;
		}

		if ($common === []) {
			return new NeverType();
		}

		return self::union(...$common);
	}

	/**
	 * The union's members keyed by identity, or null when the fast path does not apply.
	 *
	 * FiniteTypeSet does the keying and the union caches it; on top of that, intersect()
	 * hands one of the two members back instead of only comparing them, so a constant string
	 * that is also a class-string is not interchangeable with a same-valued one that is not -
	 * the class-string flag would be lost. Such unions go the slow way.
	 *
	 * @return array<string, Type>|null
	 */
	private static function finiteUnionMembers(UnionType $union): ?array
	{
		$finiteTypeSet = $union->getFiniteTypeSet();
		if ($finiteTypeSet === null || !$finiteTypeSet->isComplete()) {
			return null;
		}

		return $finiteTypeSet->getMembers();
	}

	public static function intersect(Type ...$types): Type
	{
		if (self::$cacheEnabled ??= TurboExtensionEnabler::isTypeCombinatorCacheEnabled()) {
			return TypeCombinatorCache::intersect(...$types);
		}

		return self::doIntersect(...$types);
	}

	/** @internal Delegated to from TypeCombinatorCache, which the native extension shadows to memoize it. */
	public static function doIntersect(Type ...$types): Type
	{
		$typesCount = count($types);
		if ($typesCount === 0) {
			return new NeverType();
		}

		$types = array_values($types);
		if ($typesCount === 1) {
			return $types[0];
		}

		// The comparator only orders UnionTypes relative to each other, so sorting is
		// a no-op unless there are at least two of them. Skip it in the common case.
		$unionTypesCount = 0;
		foreach ($types as $type) {
			if ($type instanceof NeverType && !$type->isExplicit()) {
				return $type;
			}
			if (!$type instanceof UnionType) {
				continue;
			}
			$unionTypesCount++;
		}

		// Fast path: the intersection of two unions whose members are all finite,
		// mutually-disjoint values (constant scalars and/or enum cases) is their
		// identity-keyed set intersection (O(n)), avoiding the O(n*m) `A & (B|C)`
		// distribution + union rebuild below. A benevolent union is the one the
		// distribution walks first and the benevolence it gives the result is kept;
		// the template union types keep their dedicated handling.
		if (
			$typesCount === 2
			&& self::isPlainOrBenevolentUnion($types[0])
			&& self::isPlainOrBenevolentUnion($types[1])
		) {
			[$a, $b] = $types;
			if ($b instanceof BenevolentUnionType && !$a instanceof BenevolentUnionType) {
				[$a, $b] = [$b, $a];
			}
			$finiteIntersection = self::intersectFiniteUnions($a, $b);
			if ($finiteIntersection !== null) {
				return $a instanceof BenevolentUnionType ? TypeUtils::toBenevolentUnion($finiteIntersection) : $finiteIntersection;
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
		if ($unionTypesCount >= 2) {
			usort($types, $sortTypes);
		}
		// transform A & (B | C) to (A & B) | (A & C)
		foreach ($types as $i => $type) {
			if (!$type instanceof UnionType) {
				continue;
			}

			$topLevelUnionSubTypes = [];
			$innerTypes = $type->getTypes();
			$innerUnionTypesCount = 0;
			foreach ($innerTypes as $innerType) {
				if (!$innerType instanceof UnionType) {
					continue;
				}
				$innerUnionTypesCount++;
				if ($innerUnionTypesCount >= 2) {
					break;
				}
			}
			if ($innerUnionTypesCount >= 2) {
				usort($innerTypes, $sortTypes);
			}
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
		$typesNeedSorting = false;
		for ($i = 0; $i < $typesCount; $i++) {
			$type = $types[$i];

			if ($type instanceof ConstantArrayType) {
				$typesNeedSorting = true;
			}

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

		if ($typesNeedSorting) {
			usort($types, static function (Type $a, Type $b): int {
				if ($a instanceof ConstantArrayType && !$b instanceof ConstantArrayType) {
					return -1;
				}
				if ($b instanceof ConstantArrayType && !$a instanceof ConstantArrayType) {
					return 1;
				}

				return 0;
			});
		}

		// Exact merges first: a member that a supertype drop below removes as
		// redundant, such as a HasPropertyType next to stdClass, can still sharpen
		// another one, such as the object shape it makes a property of required.
		// Which of the two reaches the pair first would depend on the order of the
		// members, and the intersection must not.
		for ($i = 0; $i < $typesCount; $i++) {
			for ($j = $i + 1; $j < $typesCount; $j++) {
				$intersectionType = $types[$j]->tryIntersect($types[$i]);
				if ($intersectionType !== null) {
					$types[$i] = $intersectionType;
					array_splice($types, $j--, 1);
					$typesCount--;
					continue;
				}

				$intersectionType = $types[$i]->tryIntersect($types[$j]);
				if ($intersectionType !== null) {
					$types[$j] = $intersectionType;
					array_splice($types, $i--, 1);
					$typesCount--;
					continue 2;
				}
			}
		}

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
				// no value is of both kinds
				if (TypeKind::areDisjoint($types[$i]->getKindMask(), $types[$j]->getKindMask())) {
					return new NeverType();
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
					$constArrayIsI = $types[$i] instanceof ConstantArrayType && ($types[$j] instanceof ArrayType || $types[$j] instanceof ConstantArrayType);
					$constArrayIsJ = $types[$j] instanceof ConstantArrayType && ($types[$i] instanceof ArrayType || $types[$i] instanceof ConstantArrayType);
					if ($constArrayIsI || $constArrayIsJ) {
						$constArray = $constArrayIsI ? $types[$i] : $types[$j];
						$otherArray = $constArrayIsI ? $types[$j] : $types[$i];
						$otherConstantArray = $otherArray instanceof ConstantArrayType ? $otherArray : null;

						if (
							$otherConstantArray !== null
							&& !$constArray->isUnsealed()->maybe()
							&& !$otherConstantArray->isUnsealed()->maybe()
						) {
							$merged = self::intersectDefiniteConstantArrays($constArray, $otherConstantArray);
							if ($merged instanceof NeverType) {
								if ($merged->getReason() === null) {
									$reasons = array_merge($isSuperTypeA->getReasons(), $isSuperTypeB->getReasons());
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
									// a shape that may have keys it does not list answers No for them too
									if (
										!$constArray->isOptionalKey($k)
										&& ($otherConstantArray === null || !$otherConstantArray->isUnsealed()->maybe())
									) {
										return new NeverType();
									}
									continue;
								}
								$newArray->setOffsetValueType(
									self::intersect($keyType, $otherArray->getIterableKeyType()),
									self::intersect($valueTypes[$k], $otherArray->getOffsetValueType($keyType)),
									$constArray->isOptionalKey($k) && !$hasOffset->yes(),
								);
							}
							$newArrayType = $newArray->getArray();
							if ($constArray->isList()->yes()) {
								$newArrayType = self::intersect($newArrayType, new AccessoryArrayListType());
							}
						}

						if ($constArrayIsI) {
							$types[$i] = $newArrayType;
							array_splice($types, $j, 1);
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
					return new NeverType(reason: $isSuperTypeA->getReasons()[0] ?? null);
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
			// Accessory types share their default base type: every string accessory
			// returns `string`, hasOffset() and hasOffsetValue() both return
			// `array|ArrayAccess`. Adding the same base type again narrows nothing -
			// intersection is idempotent - but the intersect() below distributes
			// `A & (B | C)` one union at a time, so n copies of `array|ArrayAccess`
			// would cost 2^n recursive calls before the duplicates are recognized at
			// the leaves. That is why isset() with many offsets used to grow
			// exponentially: each offset contributes one hasOffset().
			$baseType = $type->getDefaultBaseType();
			foreach ($accessoryBaseTypes as $addedBaseType) {
				if ($addedBaseType->equals($baseType)) {
					continue 2;
				}
			}
			$accessoryBaseTypes[] = $baseType;
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
