<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Turbo\TurboExtensionEnabler;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Combinator\AbsorbedArrayUnionRule;
use PHPStan\Type\Combinator\AtOnceUnionRemoveRule;
use PHPStan\Type\Combinator\BooleanUnionRule;
use PHPStan\Type\Combinator\DisjointFiniteUnionRemoveRule;
use PHPStan\Type\Combinator\DuplicateIntersectRule;
use PHPStan\Type\Combinator\ExactIntersectRule;
use PHPStan\Type\Combinator\FiniteTypesRemoveRule;
use PHPStan\Type\Combinator\FoldedShapeUnionRule;
use PHPStan\Type\Combinator\GeneralArrayUnionRule;
use PHPStan\Type\Combinator\IntegerUnionRemoveRule;
use PHPStan\Type\Combinator\IntersectRule;
use PHPStan\Type\Combinator\IterableUnionRule;
use PHPStan\Type\Combinator\MixedUnionRemoveRule;
use PHPStan\Type\Combinator\NumberUnionRule;
use PHPStan\Type\Combinator\OffsetValueLimitIntersectRule;
use PHPStan\Type\Combinator\OverlappingArrayUnionRule;
use PHPStan\Type\Combinator\PeelUnionRemoveRule;
use PHPStan\Type\Combinator\RemoveRule;
use PHPStan\Type\Combinator\ShapeRemoveRule;
use PHPStan\Type\Combinator\ShapeUnionRule;
use PHPStan\Type\Combinator\SingleValueUnionRule;
use PHPStan\Type\Combinator\StringUnionRule;
use PHPStan\Type\Combinator\SubsumptionIntersectRule;
use PHPStan\Type\Combinator\SubsumptionRemoveRule;
use PHPStan\Type\Combinator\SubsumptionUnionRule;
use PHPStan\Type\Combinator\SubtractedMixedRemoveRule;
use PHPStan\Type\Combinator\TypeRemoveRule;
use PHPStan\Type\Combinator\UnionRemoveRule;
use PHPStan\Type\Combinator\UnionRule;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Generic\TemplateBenevolentUnionType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateUnionType;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use function array_key_exists;
use function array_push;
use function array_slice;
use function array_values;
use function count;
use function get_class;
use function is_float;
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

	/** @var list<IntersectRule>|null */
	private static ?array $intersectRules = null;

	/** @var list<UnionRemoveRule>|null */
	private static ?array $unionRemoveRules = null;

	/** @var list<RemoveRule>|null */
	private static ?array $removeRules = null;

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

	/**
	 * The rules remove() asks in order when it removes a union, the first
	 * answer winning: all members at once where the order of removals cannot
	 * matter, then one at a time.
	 *
	 * @return list<UnionRemoveRule>
	 */
	private static function createUnionRemoveRules(): array
	{
		return [
			new IntegerUnionRemoveRule(),
			new AtOnceUnionRemoveRule(),
			new MixedUnionRemoveRule(),
			new DisjointFiniteUnionRemoveRule(),
			new PeelUnionRemoveRule(),
		];
	}

	/**
	 * The rules remove() asks in order when it removes any other type, the
	 * first answer winning.
	 *
	 * @return list<RemoveRule>
	 */
	private static function createRemoveRules(): array
	{
		return [
			new SubsumptionRemoveRule(),
			new SubtractedMixedRemoveRule(),
			new ShapeRemoveRule(),
			new TypeRemoveRule(),
			new FiniteTypesRemoveRule(),
		];
	}

	public static function remove(Type $fromType, Type $typeToRemove): Type
	{
		if (self::$cacheEnabled ??= TurboExtensionEnabler::isTypeCombinatorCacheEnabled()) {
			return TypeCombinatorCache::remove($fromType, $typeToRemove);
		}

		return self::doRemove($fromType, $typeToRemove);
	}

	/** @internal Delegated to from TypeCombinatorCache, which the native extension shadows to memoize it. */
	public static function doRemove(Type $fromType, Type $typeToRemove): Type
	{
		if ($typeToRemove instanceof UnionType) {
			foreach (self::$unionRemoveRules ??= self::createUnionRemoveRules() as $rule) {
				$removed = $rule->tryRemove($fromType, $typeToRemove);
				if ($removed !== null) {
					return $removed;
				}
			}

			return $fromType;
		}

		foreach (self::$removeRules ??= self::createRemoveRules() as $rule) {
			$removed = $rule->tryRemove($fromType, $typeToRemove);
			if ($removed !== null) {
				return $removed;
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
		// no member is an array
		if (($type->getKindMask() & TypeKind::ARRAY) === 0) {
			return $type;
		}

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

		if (
			self::countConstantArrayValueTypes($arrayTypes) <= ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT
			&& count(GeneralArrayUnionRule::getGeneralArrays($arrayTypes)) <= GeneralArrayUnionRule::GENERAL_ARRAYS_LIMIT
		) {
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
			new OverlappingArrayUnionRule(),
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
			new FoldedShapeUnionRule(),
			new GeneralArrayUnionRule(),
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
			// a union is normalized already
			if ($singleType instanceof UnionType || !$singleType->isArray()->yes()) {
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
			// Equal non-array operands are not decided by equals(): it is weaker
			// than interchangeability for some types - a benevolent
			// `(int|string)` equals a plain `int|string` and `Foo=final` equals
			// `Foo` - so they are left to findAbsorbingOperand() and the rules,
			// which keep the wider operand rather than whichever one was passed first.
			if ($a === $b || ($a->equals($b) && $a->isArray()->yes())) {
				return $a;
			}

			// union(A, B) = A when A holds B, as `float|int` holds `0`
			$absorbing = self::findAbsorbingOperand($a, $b);
			if ($absorbing !== null) {
				return $absorbing;
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

		// the kinds of the members, never - of no values - overlapping every kind,
		// and of those that are not one constant scalar value
		$kindMask = 0;
		$nonConstantKindMask = 0;
		foreach ($types as $type) {
			$memberKindMask = $type->getKindMask();
			if ($memberKindMask === 0) {
				$memberKindMask = TypeKind::ALL;
			}
			$kindMask |= $memberKindMask;
			if (count($type->getConstantScalarValues()) === 1) {
				continue;
			}
			$nonConstantKindMask |= $memberKindMask;
		}
		foreach (self::$unionRules ??= self::createUnionRules() as $rule) {
			if (($rule->getKindMask() & ($rule->mergesConstantScalarValues() ? $kindMask : $nonConstantKindMask)) === 0) {
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
	 * The union of two operands when it is known without the rules: of two that
	 * never merge into one member - of different kinds, or two different constant
	 * scalars - the union of both, of a normalized union and another operand what
	 * unionWithNormalizedUnion() answers, and when one holds the other the one that
	 * holds the other and is not held by it, and of two holding each other the
	 * later one, as SingleValueUnionRule and SubsumptionUnionRule keep it, unless
	 * tryUnion() merges them into another type or they are two values, as -0.0
	 * and 0.0. Null when neither holds the
	 * other, when one is of no kinds (never) or an unresolved template argument,
	 * which only addMarker() merges, and when either may be an array: the array
	 * rules rebuild arrays even from a supertype and its subtype, as the shapes
	 * of `array{a: int}|array{a: int, b?: int}` merge.
	 */
	private static function findAbsorbingOperand(Type $a, Type $b): ?Type
	{
		$aKinds = $a->getKindMask();
		$bKinds = $b->getKindMask();
		if (
			($aKinds & $bKinds) === 0
			&& $aKinds !== 0
			&& $bKinds !== 0
			&& (($aKinds | $bKinds) & TypeKind::ARRAY) === 0
			// true and false merge into bool
			&& (($aKinds & TypeKind::BOOL) === 0 || ($bKinds & TypeKind::BOOL) === 0)
			&& !$a instanceof UnionType
			&& !$b instanceof UnionType
			&& !$a instanceof UnresolvedTemplateArgumentType
			&& !$b instanceof UnresolvedTemplateArgumentType
		) {
			// values of different kinds never merge into one member
			return new UnionType([$a, $b], true);
		}
		if (
			($aKinds & $bKinds) === 0
			|| (($aKinds | $bKinds) & TypeKind::ARRAY) !== 0
			|| $a instanceof UnresolvedTemplateArgumentType
			|| $b instanceof UnresolvedTemplateArgumentType
		) {
			return null;
		}

		if ($a instanceof UnionType || $b instanceof UnionType) {
			return self::unionWithNormalizedUnion($a, $b, false) ?? self::unionWithNormalizedUnion($b, $a, true);
		}
		if (
			self::isValueApartFromSingleValues($a)
			&& self::isValueApartFromSingleValues($b)
			&& SingleValueUnionRule::getValueKey($a) !== SingleValueUnionRule::getValueKey($b)
		) {
			return new UnionType([$a, $b], true);
		}

		$aHoldsB = $a->isSuperTypeOf($b)->yes();
		$bHoldsA = $b->isSuperTypeOf($a)->yes();
		if ($aHoldsB !== $bHoldsA) {
			return $aHoldsB ? $a : $b;
		}
		if (
			!$aHoldsB
			|| $a->tryUnion($b) !== null
			|| $b->tryUnion($a) !== null
		) {
			return null;
		}

		// two values SingleValueUnionRule keeps apart, as -0.0 and 0.0
		$aKey = SingleValueUnionRule::getValueKey($a);
		$bKey = SingleValueUnionRule::getValueKey($b);
		if ($aKey !== null && $bKey !== null && $aKey !== $bKey) {
			return null;
		}

		return $b;
	}

	/**
	 * The union of a normalized union and another operand that one of its members
	 * holds: the union. Of a constant scalar no member holds, when the members of
	 * its kinds are single values too, which never merge with it: the union with
	 * the value added. Otherwise null, as for a union holding the operand only as
	 * a whole, like `int<0, 5>|int<6, 9>` holds `int<2, 7>`, which is left to the
	 * rules. The members are asked one by one instead of the union, which would
	 * build the set of its values for this one question.
	 */
	private static function unionWithNormalizedUnion(Type $union, Type $other, bool $otherFirst): ?Type
	{
		if (!$union instanceof UnionType || $union instanceof TemplateType || $other instanceof UnionType) {
			return null;
		}

		$otherKinds = $other->getKindMask();
		$canAdd = self::isValueApartFromSingleValues($other);
		$otherValues = $canAdd ? $other->getConstantScalarValues() : null;
		if ($otherValues !== null && is_float($otherValues[0])) {
			// -0.0 === 0.0 and NAN !== NAN, the value keys tell floats apart
			$otherValues = null;
		}
		$otherKey = SingleValueUnionRule::getValueKey($other);
		$members = $union->getTypes();
		foreach ($members as $member) {
			// a member of other kinds neither holds the value nor merges with it
			if (($member->getKindMask() & $otherKinds) === 0) {
				continue;
			}
			if ($otherValues !== null && $member->isConstantScalarValue()->yes()) {
				$memberValues = $member->getConstantScalarValues();
				// a single value holds only its own value
				if (count($memberValues) === 1 && $memberValues[0] !== $otherValues[0]) {
					continue;
				}
			}
			if (
				$member->isSuperTypeOf($other)->yes()
				// two values SingleValueUnionRule keeps apart, as -0.0 and 0.0
				&& ($otherKey === null || (SingleValueUnionRule::getValueKey($member) ?? $otherKey) === $otherKey)
			) {
				return $union;
			}
			$canAdd = $canAdd && $member->isConstantScalarValue()->yes() && SingleValueUnionRule::isSingleValue($member);
		}
		if (!$canAdd) {
			return null;
		}

		// a benevolent union with a value apart from its members is a plain one
		return new UnionType($otherFirst ? [$other, ...$members] : [...$members, $other], true);
	}

	/**
	 * A constant scalar that never merges with another single value, as booleans
	 * merge into bool: a member of its own beside the other values.
	 */
	private static function isValueApartFromSingleValues(Type $type): bool
	{
		return ($type->getKindMask() & TypeKind::BOOL) === 0
			&& $type->isConstantScalarValue()->yes()
			&& SingleValueUnionRule::isSingleValue($type)
			&& !$type->hasTemplateOrLateResolvableType();
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
			// a constant scalar holds no other type
			if ($type->isConstantScalarValue()->yes()) {
				continue;
			}
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

	/**
	 * The rules intersect() runs on the members, in this order: the exact
	 * merges, then any member holding another gives way to it.
	 *
	 * @return list<IntersectRule>
	 */
	private static function createIntersectRules(): array
	{
		$pairRules = [
			new ExactIntersectRule(),
			new SubsumptionIntersectRule(),
		];

		return [
			new DuplicateIntersectRule($pairRules),
			new OffsetValueLimitIntersectRule(),
			...$pairRules,
		];
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

		// Integers, ranges and their plain unions intersect by their intervals,
		// instead of distributing `A & (B | C)` over each pair of members.
		if ($unionTypesCount > 0) {
			$operands = [];
			foreach ($types as $type) {
				if (!$type instanceof UnionType) {
					$operands[] = [$type];
					continue;
				}
				if (get_class($type) !== UnionType::class) {
					$operands = null;
					break;
				}
				$operands[] = $type->getTypes();
			}
			$integers = $operands !== null ? ScalarUnion::intersectIntegers($operands) : null;
			if ($integers !== null) {
				return self::union(...$integers);
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

		// transform A & (B & C) to A & B & C
		$members = [];
		foreach ($types as $type) {
			if ($type instanceof IntersectionType && !$type instanceof TemplateType) {
				array_push($members, ...$type->getTypes());
				continue;
			}
			$members[] = $type;
		}

		foreach (self::$intersectRules ??= self::createIntersectRules() as $rule) {
			$members = $rule->apply($members);
		}
		$types = $members;
		$typesCount = count($types);

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

	public static function removeFalsey(Type $type): Type
	{
		return self::remove($type, StaticTypeFactory::falsey());
	}

	public static function removeTruthy(Type $type): Type
	{
		return self::remove($type, StaticTypeFactory::truthy());
	}

}
