<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Iterator;
use PHPStan\Fixture\ManyCasesTestEnum;
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
 */
#[RequiresPhp('^8.1')]
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
			'strings and null' => new UnionType([new ConstantStringType('a'), new ConstantStringType('b'), new NullType()]),
			'integers' => new UnionType([new ConstantIntegerType(1), new ConstantIntegerType(2), new ConstantIntegerType(3)]),
			'booleans' => new UnionType([new ConstantBooleanType(true), new ConstantBooleanType(false)]),
			'enum cases' => new UnionType([
				new EnumCaseObjectType(ManyCasesTestEnum::class, 'A'),
				new EnumCaseObjectType(ManyCasesTestEnum::class, 'B'),
			]),
			'enum cases superset' => new UnionType([
				new EnumCaseObjectType(ManyCasesTestEnum::class, 'A'),
				new EnumCaseObjectType(ManyCasesTestEnum::class, 'B'),
				new EnumCaseObjectType(ManyCasesTestEnum::class, 'C'),
			]),
			'mixed kinds' => new UnionType([
				new ConstantStringType('a'),
				new ConstantIntegerType(1),
				new ConstantBooleanType(true),
				new NullType(),
				new EnumCaseObjectType(ManyCasesTestEnum::class, 'A'),
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
			'string d' => new ConstantStringType('d'),
			'string z' => new ConstantStringType('z'),
			'class-string' => new ConstantStringType('DateTimeImmutable', true),
			'numeric string' => new ConstantStringType('1'),
			'integer 1' => new ConstantIntegerType(1),
			'integer 9' => new ConstantIntegerType(9),
			'true' => new ConstantBooleanType(true),
			'float' => new ConstantFloatType(1.0),
			'null' => new NullType(),
			'enum case A' => new EnumCaseObjectType(ManyCasesTestEnum::class, 'A'),
			'enum case F' => new EnumCaseObjectType(ManyCasesTestEnum::class, 'F'),
			'whole enum' => new ObjectType(ManyCasesTestEnum::class),
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

	private function assertAccepts(UnionType $type, Type $otherType): void
	{
		$finiteTypeSet = $type->getFiniteTypeSet();
		$answersPerKind = FiniteTypeSet::key($otherType) !== null
			&& $finiteTypeSet !== null
			&& $finiteTypeSet->isComplete();

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
	public function testIsSubTypeOf(UnionType $type, UnionType $otherType): void
	{
		$this->assertSame(
			self::referenceIsSubTypeOf($type, $otherType)->describe(),
			$type->isSubTypeOf($otherType)->result->describe(),
			sprintf('%s -> isSubTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	#[DataProvider('dataUnionPairs')]
	public function testIsAcceptedBy(UnionType $type, UnionType $otherType): void
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
		yield [new ConstantIntegerType(1), 'i:1'];
		yield [new ConstantBooleanType(true), 'b:1'];
		yield [new ConstantBooleanType(false), 'b:0'];
		yield [new NullType(), 'null'];
		yield [new EnumCaseObjectType(ManyCasesTestEnum::class, 'A'), 'enum:PHPStan\Fixture\ManyCasesTestEnum::A'];

		// floats do not have value identity: -0.0 === 0.0 and NAN !== NAN
		yield [new ConstantFloatType(1.0), null];
		yield [new StringType(), null];
		yield [new IntegerType(), null];
		yield [new MixedType(), null];
		yield [new ObjectType(ManyCasesTestEnum::class), null];
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
