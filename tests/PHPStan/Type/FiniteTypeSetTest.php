<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Iterator;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_map;
use function count;
use function sprintf;

/**
 * The identity map is only an optimization, so the interesting property is that it never
 * disagrees with comparing a union member by member. Every test here therefore compares the
 * real answer against a reference implementation that spells out the member-by-member loop
 * UnionType used before the map existed.
 *
 * The enum fixtures are named by string rather than by ::class, as UnionTypeTest does:
 * self-analysis excludes those files below PHP 8.1, and a ::class would have PHPStan report
 * them as unknown classes there.
 */
#[RequiresPhp('>= 8.1.0')]
class FiniteTypeSetTest extends PHPStanTestCase
{

	/**
	 * @return array<string, UnionType>
	 */
	private static function unions(): array
	{
		return [
			'strings' => new UnionType([new ConstantStringType('a'), new ConstantStringType('b'), new ConstantStringType('c')]),
			'strings superset' => new UnionType([new ConstantStringType('a'), new ConstantStringType('b'), new ConstantStringType('c'), new ConstantStringType('d')]),
			'strings reordered' => new UnionType([new ConstantStringType('c'), new ConstantStringType('b'), new ConstantStringType('a')]),
			'strings disjoint' => new UnionType([new ConstantStringType('x'), new ConstantStringType('y')]),
			'strings differing only in case' => new UnionType([new ConstantStringType('a'), new ConstantStringType('A')]),
			'strings and null' => new UnionType([new ConstantStringType('a'), new ConstantStringType('b'), new NullType()]),
			'integers' => new UnionType([new ConstantIntegerType(1), new ConstantIntegerType(2), new ConstantIntegerType(3)]),
			'booleans' => new UnionType([new ConstantBooleanType(true), new ConstantBooleanType(false)]),
			'enum cases' => new UnionType([
				new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'A'),
				new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'B'),
			]),
			'enum cases superset' => new UnionType([
				new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'A'),
				new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'B'),
				new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'C'),
			]),
			'mixed kinds' => new UnionType([
				new ConstantStringType('a'),
				new ConstantIntegerType(1),
				new ConstantBooleanType(true),
				new NullType(),
				new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'A'),
			]),
			'strings and object' => new UnionType([new ConstantStringType('a'), new ConstantStringType('b'), new ObjectType('DateTimeImmutable')]),
			'strings and float' => new UnionType([new ConstantStringType('a'), new ConstantFloatType(1.0)]),
			'strings and class-string' => new UnionType([new ConstantStringType('a'), new ConstantStringType('DateTimeImmutable', true)]),
			'strings and general string' => new UnionType([new ConstantStringType('a'), new StringType()]),
			'string and integer' => new UnionType([new ConstantStringType('a'), new IntegerType()]),
			'benevolent strings' => new BenevolentUnionType([new ConstantStringType('a'), new ConstantStringType('b')]),
			'objects' => new UnionType([new ObjectType('DateTimeImmutable'), new ObjectType('DateTime')]),
		];
	}

	/**
	 * @return array<string, Type>
	 */
	private static function otherTypes(): array
	{
		return [
			'string a' => new ConstantStringType('a'),
			'string A' => new ConstantStringType('A'),
			'string d' => new ConstantStringType('d'),
			'string z' => new ConstantStringType('z'),
			'class-string' => new ConstantStringType('DateTimeImmutable', true),
			'numeric string' => new ConstantStringType('1'),
			'integer 1' => new ConstantIntegerType(1),
			'integer 9' => new ConstantIntegerType(9),
			'true' => new ConstantBooleanType(true),
			'float' => new ConstantFloatType(1.0),
			'null' => new NullType(),
			'enum case A' => new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'A'),
			'enum case F' => new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'F'),
			'whole enum' => new ObjectType('PHPStan\Fixture\ManyCasesTestEnum'),
			'string' => new StringType(),
			'integer' => new IntegerType(),
			'object' => new ObjectType('DateTimeImmutable'),
			'mixed' => new MixedType(),
			'template' => TemplateTypeFactory::create(
				TemplateTypeScope::createWithFunction('foo'),
				'T',
				new StringType(),
				TemplateTypeVariance::createInvariant(),
			),
		];
	}

	/**
	 * @return Iterator<string, array{UnionType, Type}>
	 */
	public static function dataUnionAndOtherType(): Iterator
	{
		foreach (self::unions() as $unionName => $union) {
			foreach (self::otherTypes() as $otherName => $otherType) {
				yield sprintf('%s <-> %s', $unionName, $otherName) => [$union, $otherType];
			}
		}
	}

	/**
	 * @return Iterator<string, array{UnionType, UnionType}>
	 */
	public static function dataUnionPairs(): Iterator
	{
		foreach (self::unions() as $unionName => $union) {
			foreach (self::unions() as $otherName => $otherUnion) {
				yield sprintf('%s <-> %s', $unionName, $otherName) => [$union, $otherUnion];
			}
		}
	}

	#[DataProvider('dataUnionAndOtherType')]
	public function testIsSuperTypeOf(UnionType $type, Type $otherType): void
	{
		$this->assertSame(
			self::referenceIsSuperTypeOf($type, $otherType)->describe(),
			$type->isSuperTypeOf($otherType)->result->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	#[DataProvider('dataUnionAndOtherType')]
	public function testAccepts(UnionType $type, Type $otherType): void
	{
		$this->assertAccepts($type, $otherType);
	}

	#[DataProvider('dataUnionPairs')]
	public function testAcceptsUnion(UnionType $type, UnionType $otherType): void
	{
		$this->assertAccepts($type, $otherType);
	}

	/**
	 * Whether the map alone settles a comparison of $type against $otherType - the whole
	 * union is keyed, and $otherType is one value to look up in it.
	 */
	private static function answersFromTheMap(UnionType $type, Type $otherType): bool
	{
		$finiteTypeSet = $type->getFiniteTypeSet();

		return FiniteTypeSet::key($otherType) !== null
			&& $finiteTypeSet !== null
			&& $finiteTypeSet->isComplete();
	}

	private function assertAccepts(UnionType $type, Type $otherType): void
	{
		$answersPerKind = self::answersFromTheMap($type, $otherType);

		foreach ([true, false] as $strictTypes) {
			$this->assertSame(
				self::referenceAccepts($type, $otherType, $strictTypes)->describe(),
				$type->accepts($otherType, $strictTypes)->result->describe(),
				sprintf('%s -> accepts(%s, strictTypes: %s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()), $strictTypes ? 'true' : 'false'),
			);

			if (!$answersPerKind) {
				continue;
			}

			// Letting one member of a kind answer for all of them is only invisible as long
			// as none of them attaches a reason to its answer - reasons are per member.
			foreach ($type->getTypes() as $innerType) {
				$this->assertSame(
					[],
					$innerType->accepts($otherType, $strictTypes)->reasons,
					sprintf('%s -> accepts(%s)', $innerType->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
				);
			}
		}
	}

	#[DataProvider('dataUnionAndOtherType')]
	public function testTryRemove(UnionType $type, Type $otherType): void
	{
		$expected = self::referenceTryRemove($type, $otherType);
		$actual = $type->tryRemove($otherType);

		$this->assertSame(
			$expected === null ? null : $expected->describe(VerbosityLevel::precise()),
			$actual === null ? null : $actual->describe(VerbosityLevel::precise()),
			sprintf('%s -> tryRemove(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	#[DataProvider('dataUnionPairs')]
	#[DataProvider('dataUnionAndOtherType')]
	public function testIsSubTypeOf(UnionType $type, Type $otherType): void
	{
		$this->assertSame(
			self::referenceIsSubTypeOf($type, $otherType)->describe(),
			$type->isSubTypeOf($otherType)->result->describe(),
			sprintf('%s -> isSubTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);

		if (!self::answersFromTheMap($type, $otherType)) {
			return;
		}

		// One key lookup stands in for asking every member, which is only invisible as long
		// as none of them attaches a reason to its answer - reasons are per member.
		foreach ($type->getTypes() as $innerType) {
			$result = $otherType->isSuperTypeOf($innerType);
			$this->assertSame(
				[],
				$result->reasons,
				sprintf('%s -> isSuperTypeOf(%s)', $otherType->describe(VerbosityLevel::precise()), $innerType->describe(VerbosityLevel::precise())),
			);
			$this->assertSame([], $result->lazyReasons);
		}
	}

	#[DataProvider('dataUnionPairs')]
	#[DataProvider('dataUnionAndOtherType')]
	public function testIsAcceptedBy(UnionType $type, Type $otherType): void
	{
		foreach ([true, false] as $strictTypes) {
			$this->assertSame(
				self::referenceIsAcceptedBy($type, $otherType, $strictTypes)->describe(),
				$type->isAcceptedBy($otherType, $strictTypes)->result->describe(),
				sprintf('%s -> isAcceptedBy(%s, strictTypes: %s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()), $strictTypes ? 'true' : 'false'),
			);
		}
	}

	#[DataProvider('dataUnionPairs')]
	public function testEquals(UnionType $type, UnionType $otherType): void
	{
		$this->assertSame(
			self::referenceEquals($type, $otherType),
			$type->equals($otherType),
			sprintf('%s -> equals(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	/**
	 * @return Iterator<array-key, array{Type, ?string}>
	 */
	public static function dataKey(): Iterator
	{
		yield [new ConstantStringType('a'), 's:a'];
		yield [new ConstantStringType('a', true), 's:a'];
		// the value goes into the key verbatim - two strings that differ in any way at all
		// stand for two different values
		yield [new ConstantStringType('A'), 's:A'];
		yield [new ConstantStringType(''), 's:'];
		yield [new ConstantStringType('0'), 's:0'];
		yield [new ConstantIntegerType(1), 'i:1'];
		yield [new ConstantIntegerType(-1), 'i:-1'];
		yield [new ConstantIntegerType(0), 'i:0'];
		yield [new ConstantBooleanType(true), 'b:1'];
		yield [new ConstantBooleanType(false), 'b:0'];
		yield [new NullType(), 'null'];
		yield [new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'A'), 'enum:PHPStan\Fixture\ManyCasesTestEnum::A'];

		// floats do not have value identity: -0.0 === 0.0 and NAN !== NAN
		yield [new ConstantFloatType(1.0), null];
		yield [new StringType(), null];
		yield [new IntegerType(), null];
		yield [new MixedType(), null];
		yield [new ObjectType('PHPStan\Fixture\ManyCasesTestEnum'), null];
		// a type that merely contains a value is not the value
		yield [new IntersectionType([new ConstantStringType('a'), new AccessoryNonEmptyStringType()]), null];
		yield [new UnionType([new ConstantStringType('a'), new ConstantStringType('b')]), null];
		yield [
			TemplateTypeFactory::create(
				TemplateTypeScope::createWithFunction('foo'),
				'T',
				new StringType(),
				TemplateTypeVariance::createInvariant(),
			),
			null,
		];
	}

	#[DataProvider('dataKey')]
	public function testKey(Type $type, ?string $expectedKey): void
	{
		$this->assertSame($expectedKey, FiniteTypeSet::key($type));
	}

	/**
	 * @return Iterator<array-key, array{Type}>
	 */
	public static function dataShortcutClass(): Iterator
	{
		yield [new ConstantStringType('a')];
		yield [new ConstantStringType('A')];
		yield [new ConstantStringType('DateTimeImmutable', true)];
		yield [new ConstantStringType('')];
		yield [new ConstantIntegerType(1)];
		yield [new ConstantIntegerType(-1)];
		yield [new ConstantIntegerType(0)];
		yield [new ConstantBooleanType(true)];
		yield [new ConstantBooleanType(false)];
		yield [new NullType()];
	}

	/**
	 * key() answers for these classes straight from get_class() instead of asking the type,
	 * which is only sound while the checks it skips would have passed. Assert them here so
	 * that a change to any of these classes fails loudly rather than silently keying a type
	 * that no longer stands for exactly one value.
	 */
	#[DataProvider('dataShortcutClass')]
	public function testShortcutClassesSatisfyTheGeneralPath(Type $type): void
	{
		$this->assertTrue($type->isConstantScalarValue()->yes());
		$this->assertSame([$type], $type->getConstantScalarTypes());
		$this->assertTrue($type->equals($type));
		$this->assertNotNull(FiniteTypeSet::key($type));
	}

	/**
	 * The shortcut matches the class exactly, so a subclass has to reach the general path -
	 * and land on the very same key, or a union holding both would count them as two values.
	 */
	public function testSubclassOfAShortcutClassIsKeyedTheSameWay(): void
	{
		$subclass = new class ('a') extends ConstantStringType {

		};

		$this->assertSame(FiniteTypeSet::key(new ConstantStringType('a')), FiniteTypeSet::key($subclass));

		$set = (new UnionType([new ConstantStringType('a'), $subclass]))->getFiniteTypeSet();
		$this->assertNotNull($set);
		$this->assertCount(1, $set->getMembers());
		$this->assertCount(1, $set->getOthers());
	}

	/**
	 * A union that holds one value twice is not the union that holds it once, so the second
	 * member cannot be dropped on the floor for sharing a key. TypeCombinator never builds
	 * such a union, but the UnionType constructor is @api and does not dedupe.
	 */
	public function testUnionRepeatingOneValueIsNotAnsweredFromTheMap(): void
	{
		// the same value in two representations: the class-string flag is not part of the
		// value, so these are equals() and must therefore share a key
		$plain = new ConstantStringType('a');
		$classString = new ConstantStringType('a', true);
		$this->assertTrue($plain->equals($classString));

		$union = new UnionType([$plain, $classString]);

		// both unions have two members and both maps would hold just 'a', so keeping only
		// the first member under the shared key would call two different types the same
		$this->assertFalse($union->equals(new UnionType([$plain, new ConstantStringType('b')])));

		// and would leave the map with no member to build a union from
		$removed = $union->tryRemove($plain);
		$this->assertNotNull($removed);
		$this->assertSame('*NEVER*', $removed->describe(VerbosityLevel::precise()));

		// the repeated member goes to $others instead, which takes the union off the fast path
		$set = $union->getFiniteTypeSet();
		$this->assertNotNull($set);
		$this->assertCount(1, $set->getMembers());
		$this->assertCount(1, $set->getOthers());
		$this->assertFalse($set->isComplete());
	}

	/**
	 * Types sharing a key must be interchangeable, so they have to be equal - and equal
	 * types must never end up under different keys.
	 */
	#[DataProvider('dataUnionAndOtherType')]
	public function testKeyAgreesWithEquals(UnionType $type, Type $otherType): void
	{
		$otherKey = FiniteTypeSet::key($otherType);
		$equal = [];
		$sameKey = [];
		foreach ($type->getTypes() as $i => $innerType) {
			$innerKey = FiniteTypeSet::key($innerType);
			if ($innerKey === null || $otherKey === null) {
				continue;
			}

			$equal[$i] = $innerType->equals($otherType);
			$sameKey[$i] = $innerKey === $otherKey;
		}

		$this->assertSame(
			$equal,
			$sameKey,
			sprintf('%s <-> %s', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	/**
	 * Members of one kind stand in for each other when the set is asked about a value it does
	 * not hold, so a kind must never span two classes - nor two enums, which answer for their
	 * own cases only.
	 */
	public function testEachKindIsRepresentedOnce(): void
	{
		$set = (new UnionType([
			new ConstantStringType('a'),
			new ConstantStringType('b'),
			new ConstantIntegerType(1),
			new ConstantIntegerType(2),
			new ConstantBooleanType(true),
			new NullType(),
			new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'A'),
			new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'B'),
			new EnumCaseObjectType('PHPStan\Fixture\AnotherTestEnum', 'ONE'),
		]))->getFiniteTypeSet();

		$this->assertNotNull($set);

		$describe = static fn (Type $type): string => $type->describe(VerbosityLevel::precise());

		// the first member of every kind but the queried one, in the union's order
		$this->assertSame(
			['1', 'true', 'null', 'PHPStan\Fixture\ManyCasesTestEnum::A', 'PHPStan\Fixture\AnotherTestEnum::ONE'],
			array_map($describe, $set->getRepresentativesOfOtherKinds(new ConstantStringType('zzz'))),
		);

		// one enum does not answer for another's cases, so both are represented
		$this->assertSame(
			["'a'", '1', 'true', 'null', 'PHPStan\Fixture\AnotherTestEnum::ONE'],
			array_map($describe, $set->getRepresentativesOfOtherKinds(new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'C'))),
		);

		// a type of no keyed kind is answered by every one of the six kinds
		$this->assertCount(6, $set->getRepresentativesOfOtherKinds(new ObjectType('DateTimeImmutable')));
	}

	/**
	 * A union of one kind has nobody to speak for a value it does not hold, so accepts()
	 * has nothing left to consult and the plain no stands.
	 */
	public function testTheOnlyKindRepresentsNoOtherKind(): void
	{
		$set = FiniteTypeSet::create([new ConstantStringType('a'), new ConstantStringType('b')]);

		$this->assertNotNull($set);
		$this->assertSame([], $set->getRepresentativesOfOtherKinds(new ConstantStringType('zzz')));
	}

	/**
	 * @return Iterator<string, array{list<Type>, list<Type>, TrinaryLogic}>
	 */
	public static function dataContainedIn(): Iterator
	{
		yield 'same members' => [
			[new ConstantStringType('a'), new ConstantStringType('b')],
			[new ConstantStringType('a'), new ConstantStringType('b')],
			TrinaryLogic::createYes(),
		];
		yield 'same members, reordered' => [
			[new ConstantStringType('a'), new ConstantStringType('b')],
			[new ConstantStringType('b'), new ConstantStringType('a')],
			TrinaryLogic::createYes(),
		];
		yield 'proper subset' => [
			[new ConstantStringType('a'), new ConstantStringType('b')],
			[new ConstantStringType('a'), new ConstantStringType('b'), new ConstantStringType('c')],
			TrinaryLogic::createYes(),
		];
		yield 'single member, held' => [
			[new ConstantStringType('a')],
			[new ConstantStringType('a'), new ConstantStringType('b')],
			TrinaryLogic::createYes(),
		];
		// the other way round: containment is not symmetric, one member is missing
		yield 'proper superset' => [
			[new ConstantStringType('a'), new ConstantStringType('b'), new ConstantStringType('c')],
			[new ConstantStringType('a'), new ConstantStringType('b')],
			TrinaryLogic::createMaybe(),
		];
		yield 'one of two held' => [
			[new ConstantStringType('a'), new ConstantStringType('b')],
			[new ConstantStringType('b'), new ConstantStringType('c')],
			TrinaryLogic::createMaybe(),
		];
		yield 'none held' => [
			[new ConstantStringType('a'), new ConstantStringType('b')],
			[new ConstantStringType('x'), new ConstantStringType('y')],
			TrinaryLogic::createNo(),
		];
		// none held, and the other set is the smaller one - what counts is how many of *this*
		// set's members are missing, not how many the other set has
		yield 'none held, smaller other set' => [
			[new ConstantStringType('a'), new ConstantStringType('b')],
			[new ConstantStringType('x')],
			TrinaryLogic::createNo(),
		];
		yield 'single member, not held' => [
			[new ConstantStringType('a')],
			[new ConstantStringType('b')],
			TrinaryLogic::createNo(),
		];
		// the keys carry the kind, so a value is never held by a member of another kind
		yield 'same values of another kind' => [
			[new ConstantIntegerType(1), new ConstantIntegerType(0)],
			[new ConstantStringType('1'), new ConstantBooleanType(false)],
			TrinaryLogic::createNo(),
		];
		yield 'across kinds' => [
			[new ConstantStringType('a'), new ConstantIntegerType(1), new NullType()],
			[new NullType(), new ConstantStringType('a'), new ConstantIntegerType(1)],
			TrinaryLogic::createYes(),
		];
	}

	/**
	 * @param list<Type> $types
	 * @param list<Type> $otherTypes
	 */
	#[DataProvider('dataContainedIn')]
	public function testContainedIn(array $types, array $otherTypes, TrinaryLogic $expected): void
	{
		$set = FiniteTypeSet::create($types);
		$otherSet = FiniteTypeSet::create($otherTypes);
		$this->assertNotNull($set);
		$this->assertNotNull($otherSet);

		$this->assertSame($expected->describe(), $set->containedIn($otherSet)->describe());
	}

	/**
	 * @return Iterator<string, array{list<Type>, Type, TrinaryLogic}>
	 */
	public static function dataContainedInKey(): Iterator
	{
		// the only way a set is wholly under one value is by being that one value
		yield 'the only member' => [[new ConstantStringType('a')], new ConstantStringType('a'), TrinaryLogic::createYes()];
		yield 'one of two members' => [
			[new ConstantStringType('a'), new ConstantStringType('b')],
			new ConstantStringType('a'),
			TrinaryLogic::createMaybe(),
		];
		yield 'the last of many members' => [
			[new ConstantStringType('a'), new ConstantStringType('b'), new ConstantStringType('c')],
			new ConstantStringType('c'),
			TrinaryLogic::createMaybe(),
		];
		yield 'not held' => [
			[new ConstantStringType('a'), new ConstantStringType('b')],
			new ConstantStringType('z'),
			TrinaryLogic::createNo(),
		];
		yield 'not held by a single-member set' => [
			[new ConstantStringType('a')],
			new ConstantStringType('z'),
			TrinaryLogic::createNo(),
		];
		// the key carries the kind, so the same value of another kind is not held either
		yield 'the same value of another kind' => [
			[new ConstantIntegerType(1)],
			new ConstantStringType('1'),
			TrinaryLogic::createNo(),
		];
		yield 'one of two kinds' => [
			[new ConstantStringType('a'), new NullType()],
			new NullType(),
			TrinaryLogic::createMaybe(),
		];
	}

	/**
	 * @param list<Type> $types
	 */
	#[DataProvider('dataContainedInKey')]
	public function testContainedInKey(array $types, Type $value, TrinaryLogic $expected): void
	{
		$set = FiniteTypeSet::create($types);
		$this->assertNotNull($set);

		$key = FiniteTypeSet::key($value);
		$this->assertNotNull($key);

		$this->assertSame($expected->describe(), $set->containedInKey($key)->describe());

		// the same answer containedIn() gives against the set of just that one value, which
		// is what a single value is being compared as
		$singleMemberSet = FiniteTypeSet::create([$value]);
		$this->assertNotNull($singleMemberSet);
		$this->assertSame($expected->describe(), $set->containedIn($singleMemberSet)->describe());
	}

	public function testUnkeyableMembersDoNotDefeatTheSet(): void
	{
		$set = (new UnionType([
			new ConstantStringType('a'),
			new ObjectType('DateTimeImmutable'),
			new ConstantStringType('b'),
		]))->getFiniteTypeSet();

		$this->assertNotNull($set);
		$this->assertFalse($set->isComplete());
		$this->assertTrue($set->has('s:a'));
		$this->assertTrue($set->has('s:b'));
		$this->assertCount(1, $set->getOthers());
	}

	/**
	 * An incomplete set speaks for its keyed members only, so a miss is not the union's
	 * answer - the members it could not key still have to be asked, and here each of them
	 * answers differently than the map would have.
	 */
	public function testMissInAnIncompleteSetStillConsultsTheOtherMembers(): void
	{
		$union = new UnionType([new ConstantStringType('a'), new ConstantStringType('b'), new StringType()]);
		$set = $union->getFiniteTypeSet();
		$this->assertNotNull($set);
		$this->assertFalse($set->isComplete());
		$this->assertFalse($set->has('s:z'));

		// 'z' is not in the map, but the general string member holds it
		$this->assertSame('Yes', $union->isSuperTypeOf(new ConstantStringType('z'))->result->describe());
		$this->assertSame('Yes', $union->accepts(new ConstantStringType('z'), true)->result->describe());

		// removing 'a' must not drop the member the map does not know about
		$removed = $union->tryRemove(new ConstantStringType('a'));
		$this->assertNotNull($removed);
		$this->assertSame("'b'|string", $removed->describe(VerbosityLevel::precise()));
	}

	/**
	 * Union-against-union answers need both sets to speak for their whole union: here the
	 * maps are identical while the unions are not, so reading the answer off them alone
	 * would call two different types the same.
	 */
	public function testUnionComparisonsRequireBothSetsToBeComplete(): void
	{
		$union = new UnionType([new ConstantStringType('a'), new ObjectType('DateTime')]);
		$otherUnion = new UnionType([new ConstantStringType('a'), new ObjectType('DateTimeImmutable')]);

		$set = $union->getFiniteTypeSet();
		$otherSet = $otherUnion->getFiniteTypeSet();
		$this->assertNotNull($set);
		$this->assertNotNull($otherSet);
		$this->assertFalse($set->isComplete());
		$this->assertFalse($otherSet->isComplete());
		$this->assertSame('Yes', $set->containedIn($otherSet)->describe());

		$this->assertFalse($union->equals($otherUnion));
		$this->assertSame('Maybe', $union->isSubTypeOf($otherUnion)->result->describe());
		$this->assertSame('Maybe', $union->isAcceptedBy($otherUnion, true)->result->describe());
	}

	/**
	 * @return Iterator<string, array{list<Type>, bool}>
	 */
	public static function dataHasClassStringMember(): Iterator
	{
		yield 'plain strings' => [[new ConstantStringType('a'), new ConstantStringType('b')], false];
		yield 'a value that names a class' => [[new ConstantStringType('a'), new ConstantStringType('DateTimeImmutable')], true];
		yield 'the class-string flag' => [[new ConstantStringType('a'), new ConstantStringType('Zzz', true)], true];
		// every member is asked, but only a string one can answer anything but no - not even
		// an enum case, whose class name does name a class
		yield 'no strings at all' => [
			[
				new ConstantIntegerType(1),
				new ConstantBooleanType(true),
				new NullType(),
				new EnumCaseObjectType('PHPStan\Fixture\ManyCasesTestEnum', 'A'),
			],
			false,
		];
	}

	/**
	 * @param list<Type> $types
	 */
	#[DataProvider('dataHasClassStringMember')]
	public function testHasClassStringMember(array $types, bool $expected): void
	{
		$set = FiniteTypeSet::create($types);
		$this->assertNotNull($set);

		$this->assertSame($expected, $set->hasClassStringMember());
		// answered from the cache the second time round, with the same answer
		$this->assertSame($expected, $set->hasClassStringMember());
	}

	public function testUnionWithoutFiniteMembersHasNoSet(): void
	{
		$this->assertNull((new UnionType([new StringType(), new IntegerType()]))->getFiniteTypeSet());
	}

	/**
	 * Mirrors UnionType::isSuperTypeOf() as it looked before the identity map, for types
	 * that reach its member loop - which none of the delegating or late-resolvable ones in
	 * dataUnionAndOtherType() are.
	 */
	private static function referenceIsSuperTypeOf(UnionType $type, Type $otherType): TrinaryLogic
	{
		$results = [];
		foreach ($type->getTypes() as $innerType) {
			$result = $innerType->isSuperTypeOf($otherType);
			if ($result->yes()) {
				return $result->result;
			}
			$results[] = $result->result;
		}

		return TrinaryLogic::createNo()->or(...$results);
	}

	/** Mirrors UnionType::isSubTypeOf() as it looked before the identity map. */
	private static function referenceIsSubTypeOf(UnionType $type, Type $otherType): TrinaryLogic
	{
		return TrinaryLogic::extremeIdentity(...array_map(
			static fn (Type $innerType): TrinaryLogic => $otherType->isSuperTypeOf($innerType)->result,
			$type->getTypes(),
		));
	}

	/**
	 * Mirrors UnionType::accepts() as it looked before the identity map, minus the branches
	 * for iterables, callables and intersections - none of the types in
	 * dataUnionAndOtherType() take them.
	 */
	private static function referenceAccepts(UnionType $type, Type $otherType, bool $strictTypes): TrinaryLogic
	{
		foreach (UnionType::EQUAL_UNION_CLASSES as $baseClass => $classes) {
			if (!$otherType->equals(new ObjectType($baseClass))) {
				continue;
			}

			$union = TypeCombinator::union(
				...array_map(static fn (string $objectClass): Type => new ObjectType($objectClass), $classes),
			);
			if (self::referenceAccepts($type, $union, $strictTypes)->yes()) {
				return TrinaryLogic::createYes();
			}
			break;
		}

		$result = TrinaryLogic::createNo();
		foreach ($type->getTypes() as $innerType) {
			$result = $result->or($innerType->accepts($otherType, $strictTypes)->result);
		}
		if ($result->yes()) {
			return $result;
		}

		if ($otherType instanceof CompoundType && !$otherType instanceof TemplateType) {
			return $otherType->isAcceptedBy($type, $strictTypes)->result;
		}

		if ($otherType->isEnum()->yes() && !$type->isEnum()->no()) {
			$enumCasesUnion = TypeCombinator::union(...$otherType->getEnumCases());
			if (!$otherType->equals($enumCasesUnion)) {
				return self::referenceAccepts($type, $enumCasesUnion, $strictTypes);
			}
		}

		return $result;
	}

	/** Mirrors UnionType::isAcceptedBy() as it looked before the identity map. */
	private static function referenceIsAcceptedBy(UnionType $type, Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof BenevolentUnionType) {
			$result = TrinaryLogic::createNo();
			foreach ($type->getTypes() as $innerType) {
				$result = $result->or($acceptingType->accepts($innerType, $strictTypes)->result);
			}

			return $result;
		}

		return TrinaryLogic::extremeIdentity(...array_map(
			static fn (Type $innerType): TrinaryLogic => $acceptingType->accepts($innerType, $strictTypes)->result,
			$type->getTypes(),
		));
	}

	/** Mirrors UnionType::equals() as it looked before the identity map. */
	private static function referenceEquals(UnionType $type, UnionType $otherType): bool
	{
		$otherTypes = $otherType->getTypes();
		if (count($type->getTypes()) !== count($otherTypes)) {
			return false;
		}

		foreach ($type->getTypes() as $innerType) {
			$match = false;
			foreach ($otherTypes as $i => $otherInnerType) {
				if (!$innerType->equals($otherInnerType)) {
					continue;
				}

				$match = true;
				unset($otherTypes[$i]);
				break;
			}

			if (!$match) {
				return false;
			}
		}

		return count($otherTypes) === 0;
	}

	/** Mirrors UnionType::tryRemove() as it looked before the identity map. */
	private static function referenceTryRemove(UnionType $type, Type $typeToRemove): ?Type
	{
		$innerTypes = [];
		$changed = false;
		foreach ($type->getTypes() as $innerType) {
			$removed = TypeCombinator::remove($innerType, $typeToRemove);
			if (!$removed->equals($innerType)) {
				$changed = true;
			}
			if ($removed instanceof NeverType) {
				continue;
			}
			if ($removed instanceof UnionType) {
				foreach ($removed->getTypes() as $removedInnerType) {
					$innerTypes[] = $removedInnerType;
				}
			} else {
				$innerTypes[] = $removed;
			}
		}

		if (!$changed) {
			return null;
		}

		if (count($innerTypes) === 0) {
			return new NeverType();
		}

		if (count($innerTypes) === 1) {
			return $innerTypes[0];
		}

		return new UnionType($innerTypes);
	}

}
