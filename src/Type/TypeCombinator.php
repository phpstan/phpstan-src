<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Turbo\TurboExtensionEnabler;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\Combinator\AbsorbedArrayUnionRule;
use PHPStan\Type\Combinator\BooleanUnionRule;
use PHPStan\Type\Combinator\FoldedShapeUnionRule;
use PHPStan\Type\Combinator\GeneralArrayUnionRule;
use PHPStan\Type\Combinator\IterableUnionRule;
use PHPStan\Type\Combinator\NumberUnionRule;
use PHPStan\Type\Combinator\ShapeUnionRule;
use PHPStan\Type\Combinator\SingleValueUnionRule;
use PHPStan\Type\Combinator\StringUnionRule;
use PHPStan\Type\Combinator\SubsumptionUnionRule;
use PHPStan\Type\Combinator\UnionRule;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateBenevolentUnionType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateUnionType;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use function array_key_exists;
use function array_merge;
use function array_slice;
use function array_splice;
use function array_values;
use function count;
use function get_class;
use function spl_object_id;
use function usort;

/**
 * @api
 */
#[ReferencedByTurboExtension(key: 'typeCombinator')]
final class TypeCombinator
{

	/** @var list<UnionRule>|null */
	private static ?array $unionRules = null;

	/** @var list<UnionRule>|null */
	private static ?array $widenRules = null;

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

		$types = [...$otherTypes, ...$arrayTypes];
		foreach (self::$widenRules ??= self::createWidenRules() as $rule) {
			$types = $rule->apply($types, []);
		}

		$widened = self::union(...$types);
		if ($type instanceof BenevolentUnionType) {
			return TypeUtils::toBenevolentUnion($widened);
		}

		return $widened;
	}

	/**
	 * The rules union() runs, in this order: the scalars merge kind by kind,
	 * the arrays merge with each other, and then any member holding another
	 * takes its place.
	 *
	 * @return list<UnionRule>
	 */
	private static function createUnionRules(): array
	{
		return [
			new SingleValueUnionRule(),
			new NumberUnionRule(),
			new StringUnionRule(),
			new BooleanUnionRule(),
			new IterableUnionRule(),
			new AbsorbedArrayUnionRule(),
			new ShapeUnionRule(),
			new GeneralArrayUnionRule(),
			new SubsumptionUnionRule(),
		];
	}

	/**
	 * The rules widen() runs on the members before union() runs its own: they
	 * give up precision to keep big joins of arrays small.
	 *
	 * @return list<UnionRule>
	 */
	private static function createWidenRules(): array
	{
		return [
			new GeneralArrayUnionRule(),
			new FoldedShapeUnionRule(),
		];
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

		// the kinds of the members, never - of no values - overlapping every kind
		$kindMask = 0;
		foreach ($types as $type) {
			$memberKindMask = $type->getKindMask();
			$kindMask |= $memberKindMask === 0 ? TypeKind::ALL : $memberKindMask;
		}
		foreach (self::$unionRules ??= self::createUnionRules() as $rule) {
			if (($rule->getKindMask() & $kindMask) === 0) {
				continue;
			}
			$types = $rule->apply($types, $alreadyNormalized);
		}
		if (count($types) === 1) {
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
				return new BenevolentUnionType($types, true);
			}
		}

		return new UnionType($types, true);
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

	public static function removeFalsey(Type $type): Type
	{
		return self::remove($type, StaticTypeFactory::falsey());
	}

	public static function removeTruthy(Type $type): Type
	{
		return self::remove($type, StaticTypeFactory::truthy());
	}

}
