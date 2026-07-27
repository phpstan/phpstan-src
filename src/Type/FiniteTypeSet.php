<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\TemplateType;
use function array_key_exists;
use function array_keys;
use function count;
use function is_bool;
use function is_int;
use function is_string;

/**
 * A union's members indexed by value identity.
 *
 * Membership among finite values - null, constant scalars other than floats, and enum
 * cases - is exact-value equality: two of them are interchangeable iff they are equals(),
 * and any two that are not equals() are disjoint. Comparing a value against a union is
 * therefore a set lookup, which an identity-keyed map answers in O(1) instead of scanning
 * every member. A trie would only pay off for prefix or pattern queries, and none of these
 * comparisons are that. Comparing two such unions drops from O(n*m) member comparisons to
 * O(n+m).
 *
 * Members that cannot be keyed this way are kept aside in $others, so a single object type
 * next to fifty constant strings does not defeat the optimization - it only means callers
 * still have to consult those few members the slow way.
 *
 * @see UnionType::getFiniteTypeSet()
 * @internal
 */
final class FiniteTypeSet
{

	private const NULL_KEY = 'null';

	private const INTEGER_KEY_PREFIX = 'i:';

	private const BOOLEAN_KEY_PREFIX = 'b:';

	private const STRING_KEY_PREFIX = 's:';

	private const ENUM_CASE_KEY_PREFIX = 'enum:';

	private ?bool $hasClassStringMember = null;

	/**
	 * @param array<string, Type> $members
	 * @param array<string, Type> $membersByKind
	 * @param list<Type> $others
	 */
	private function __construct(private array $members, private array $membersByKind, private array $others)
	{
	}

	/**
	 * Returns null when none of the types is a finite value - there is nothing to look up
	 * then, and the caller would only pay for building an empty map.
	 *
	 * Two types standing for the same value are not merged: the second one goes to $others
	 * so that the set never claims a union has fewer members than it does.
	 *
	 * @param list<Type> $types
	 */
	public static function create(array $types): ?self
	{
		$members = [];
		$membersByKind = [];
		$others = [];
		foreach ($types as $type) {
			$keyAndKind = self::keyAndKind($type);
			if ($keyAndKind === null || array_key_exists($keyAndKind[0], $members)) {
				$others[] = $type;
				continue;
			}

			[$key, $kind] = $keyAndKind;
			$members[$key] = $type;
			$membersByKind[$kind] ??= $type;
		}

		if ($members === []) {
			return null;
		}

		return new self($members, $membersByKind, $others);
	}

	/**
	 * Identity key of a single finite value: two types share a key iff they are equals(),
	 * and types with different keys are disjoint.
	 *
	 * Returns null for anything else. Floats are excluded because equals() does not agree
	 * with value identity for them (-0.0 === 0.0, NAN !== NAN). A type that merely contains
	 * a finite value - an intersection with an accessory type, a whole single-case enum, a
	 * conditional type resolving to a constant - is excluded by the equals() check: only a
	 * type that *is* the value can stand in for it. Template types are excluded outright,
	 * their comparison semantics are not value identity.
	 */
	public static function key(Type $type): ?string
	{
		return self::keyAndKind($type)[0] ?? null;
	}

	/**
	 * The identity key together with the kind of value it is.
	 *
	 * Members of one kind are the same type class - and, for enum cases, of the same enum -
	 * so they answer accepts() identically for every value none of them holds. The kind is
	 * what makes one member stand in for all its siblings there.
	 *
	 * @return array{string, string}|null
	 */
	private static function keyAndKind(Type $type): ?array
	{
		if ($type instanceof TemplateType) {
			return null;
		}

		// Only a bare case is safe to key by class + case name: for anything else -
		// $this & Enum::C, a whole single-case enum, an enum subtracted to one case -
		// EnumCaseObjectType::equals() is false because it requires an EnumCaseObjectType,
		// which makes instanceof exactly the question being asked here. Type::getEnumCases()
		// would answer it too, but only by resolving a ClassReflection - and a key has to be
		// derivable from the type alone, on every comparison, without reflection.
		// Key by class + case name, the identity equals() compares (describe() would also
		// fold in a subtracted type, which equals() ignores).
		if ($type instanceof EnumCaseObjectType) { // @phpstan-ignore phpstanApi.instanceofType
			$kind = self::ENUM_CASE_KEY_PREFIX . $type->getClassName();

			return [$kind . '::' . $type->getEnumCaseName(), $kind];
		}

		if (!$type->isConstantScalarValue()->yes()) {
			return null;
		}

		$scalarTypes = $type->getConstantScalarTypes();
		if (count($scalarTypes) !== 1 || !$scalarTypes[0]->equals($type)) {
			return null;
		}

		$value = $scalarTypes[0]->getValue();
		if ($value === null) {
			return [self::NULL_KEY, self::NULL_KEY];
		}
		if (is_int($value)) {
			return [self::INTEGER_KEY_PREFIX . $value, self::INTEGER_KEY_PREFIX];
		}
		if (is_bool($value)) {
			return [self::BOOLEAN_KEY_PREFIX . ($value ? '1' : '0'), self::BOOLEAN_KEY_PREFIX];
		}
		if (is_string($value)) {
			return [self::STRING_KEY_PREFIX . $value, self::STRING_KEY_PREFIX];
		}

		return null;
	}

	/**
	 * One member per kind other than $type's own, in the union's order.
	 *
	 * For a value the set does not hold, every member of $type's kind answers accepts() no,
	 * and the remaining members answer per kind - so or()-ing over these few is the same
	 * answer as or()-ing over all of them.
	 *
	 * @return list<Type>
	 */
	public function getRepresentativesOfOtherKinds(Type $type): array
	{
		$kind = self::keyAndKind($type)[1] ?? null;
		$representatives = [];
		foreach ($this->membersByKind as $memberKind => $member) {
			if ($memberKind === $kind) {
				continue;
			}

			$representatives[] = $member;
		}

		return $representatives;
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->members);
	}

	/** Whether every member of the union is keyed, so the map answers for the whole union. */
	public function isComplete(): bool
	{
		return $this->others === [];
	}

	/**
	 * Members in the union's own order.
	 *
	 * @return array<string, Type>
	 */
	public function getMembers(): array
	{
		return $this->members;
	}

	/** @return list<Type> */
	public function getOthers(): array
	{
		return $this->others;
	}

	/**
	 * Yes when every keyed member is also in $other, no when none of them is.
	 *
	 * Only keyed members are compared - call isComplete() first when the answer has to
	 * hold for the whole union.
	 */
	public function containedIn(self $other): TrinaryLogic
	{
		$contained = 0;
		foreach (array_keys($this->members) as $key) {
			if (!$other->has($key)) {
				continue;
			}

			$contained++;
		}

		if ($contained === count($this->members)) {
			return TrinaryLogic::createYes();
		}

		if ($contained === 0) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createMaybe();
	}

	/**
	 * Whether a constant string member might also be a class-string.
	 *
	 * The class-string flag is part of a constant string's representation but not of its
	 * value, so operations that pick a member to hand back - as opposed to merely comparing
	 * values - cannot treat two same-valued constant strings as interchangeable. Answering
	 * this costs a reflection lookup per string member, so it is computed on demand: only
	 * combining operations ask.
	 */
	public function hasClassStringMember(): bool
	{
		if ($this->hasClassStringMember !== null) {
			return $this->hasClassStringMember;
		}

		$this->hasClassStringMember = false;
		foreach ($this->members as $member) {
			if (!$member->isString()->yes()) {
				continue;
			}
			if ($member->isClassString()->no()) {
				continue;
			}

			$this->hasClassStringMember = true;
			break;
		}

		return $this->hasClassStringMember;
	}

}
