<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateType;
use function array_diff_key;
use function array_key_exists;
use function count;
use function get_class;
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
	 * so that the set never claims a union has fewer members than it does. TypeCombinator
	 * never builds such a union, but the UnionType constructor is @api and does not dedupe,
	 * and a union holding one value twice is not the union holding it once - merging them
	 * would let equals() call 'a'|'a' and 'a'|'b' the same type, and leave tryRemove() with
	 * no member to build a union from.
	 *
	 * @param list<Type> $types
	 */
	public static function create(array $types): ?self
	{
		$members = [];
		$membersByKind = [];
		$others = [];
		foreach ($types as $type) {
			$key = self::key($type);
			if ($key === null || array_key_exists($key, $members)) {
				$others[] = $type;
				continue;
			}

			$members[$key] = $type;
			$membersByKind[self::kind($type)] ??= $type;
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
		$enumCaseObject = $type->getEnumCaseObject();
		if ($enumCaseObject !== null && $enumCaseObject->equals($type)) {
			return self::ENUM_CASE_KEY_PREFIX . $enumCaseObject->getClassName() . '::' . $enumCaseObject->getEnumCaseName();
		}

		$scalarTypes = $type->getConstantScalarTypes();
		if (count($scalarTypes) === 1 && $scalarTypes[0]->equals($type)) {
			$value = $scalarTypes[0]->getValue();
			if ($value === null) {
				return self::NULL_KEY;
			}
			if (is_int($value)) {
				return self::INTEGER_KEY_PREFIX . $value;
			}
			if (is_bool($value)) {
				return self::BOOLEAN_KEY_PREFIX . ($value ? '1' : '0');
			}
			if (is_string($value)) {
				return self::STRING_KEY_PREFIX . $value;
			}
		}

		return null;
	}

	/**
	 * The kind of value a type stands for.
	 *
	 * Members of one kind answer accepts() identically for every value none of them holds,
	 * which is what lets one of them stand in for all its siblings there. Being of the same
	 * class is enough for that - accepts() on a constant scalar only asks whether the other
	 * type equals it - except for enum cases, where the enum is part of the answer.
	 *
	 * Only meaningful for a type that key() keys; anything else gets a kind of its own,
	 * which merely costs it a representative.
	 */
	private static function kind(Type $type): string
	{
		$enumCaseObject = $type->getEnumCaseObject();
		if ($enumCaseObject !== null && $enumCaseObject->equals($type)) {
			return self::ENUM_CASE_KEY_PREFIX . $enumCaseObject->getClassName();
		}

		return get_class($type);
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
		$kind = self::kind($type);
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
		// One array_diff_key() rather than a lookup per member: the keys are what both sets
		// are indexed by, so the whole comparison is a single C-level hash join. Asking for
		// what is missing rather than for what is shared makes the yes answer the cheap one -
		// it is the one that costs nothing to collect, and the one all three callers are
		// after (isAcceptedBy() and equals() want nothing else).
		$missing = count(array_diff_key($this->members, $other->members));

		if ($missing === 0) {
			return TrinaryLogic::createYes();
		}

		if ($missing === count($this->members)) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createMaybe();
	}

	/**
	 * containedIn() against the one-member set holding just $key.
	 *
	 * Yes only when this set holds nothing besides that value, no when it does not hold it
	 * at all, maybe in between - the same three answers as containedIn(), which is what a
	 * single value is being compared as here.
	 *
	 * Only keyed members are compared - call isComplete() first when the answer has to
	 * hold for the whole union.
	 */
	public function containedInKey(string $key): TrinaryLogic
	{
		if (!$this->has($key)) {
			return TrinaryLogic::createNo();
		}

		if (count($this->members) === 1) {
			return TrinaryLogic::createYes();
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
	 *
	 * Every member is asked, no matter its kind: a keyed member is an instance of one of the
	 * five classes key() accepts, and every one of them but ConstantStringType answers
	 * isClassString() no outright - which is also the only one whose answer costs anything.
	 */
	public function hasClassStringMember(): bool
	{
		if ($this->hasClassStringMember !== null) {
			return $this->hasClassStringMember;
		}

		$this->hasClassStringMember = false;
		foreach ($this->members as $member) {
			if ($member->isClassString()->no()) {
				continue;
			}

			$this->hasClassStringMember = true;
			break;
		}

		return $this->hasClassStringMember;
	}

}
