<?php declare(strict_types = 1);

namespace PHPStan\Type;

use DoctrineIntersectionTypeIsSupertypeOf\Collection;
use Iterator;
use ObjectTypeEnums\FooEnum;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Test\ClassWithToString;
use Traversable;
use function count;
use function sprintf;
use const PHP_VERSION_ID;

class IntersectionTypeTest extends PHPStanTestCase
{

	public static function dataAccepts(): Iterator
	{
		$intersectionType = new IntersectionType([
			new ObjectType('Collection'),
			new IterableType(new MixedType(), new ObjectType('Item')),
		]);

		yield [
			$intersectionType,
			$intersectionType,
			TrinaryLogic::createYes(),
		];

		yield [
			$intersectionType,
			new ObjectType('Collection'),
			TrinaryLogic::createNo(),
		];

		yield [
			$intersectionType,
			new IterableType(new MixedType(), new ObjectType('Item')),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new IntersectionType([
				new ObjectType(ClassWithToString::class),
				new HasPropertyType('foo'),
			]),
			new StringType(),
			TrinaryLogic::createNo(),
		];

		yield [
			TypeCombinator::intersect(new ArrayType(new MixedType(), new MixedType()), new CallableType()),
			new CallableType(),
			TrinaryLogic::createMaybe(),
		];
	}

	#[DataProvider('dataAccepts')]
	public function testAccepts(IntersectionType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true)->result;
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataIsCallable(): array
	{
		return [
			[
				new IntersectionType([
					new ConstantArrayType(
						[new ConstantIntegerType(0), new ConstantIntegerType(1)],
						[new ConstantStringType('Closure'), new ConstantStringType('bind')],
					),
					new IterableType(new MixedType(), new ObjectType('Item')),
				]),
				TrinaryLogic::createYes(),
			],
			[
				new IntersectionType([
					new ArrayType(new MixedType(), new MixedType()),
					new IterableType(new MixedType(), new ObjectType('Item')),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new IntersectionType([
					new ObjectType('ArrayObject'),
					new IterableType(new MixedType(), new ObjectType('Item')),
				]),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	#[DataProvider('dataIsCallable')]
	public function testIsCallable(IntersectionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isCallable();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isCallable()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataIsSuperTypeOf(): Iterator
	{
		$intersectionTypeA = new IntersectionType([
			new ObjectType('ArrayObject'),
			new IterableType(new MixedType(), new ObjectType('Item')),
		]);

		yield [
			$intersectionTypeA,
			$intersectionTypeA,
			TrinaryLogic::createYes(),
		];

		yield [
			$intersectionTypeA,
			new ObjectType('ArrayObject'),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$intersectionTypeA,
			new IterableType(new MixedType(), new ObjectType('Item')),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$intersectionTypeA,
			new ArrayType(new MixedType(), new ObjectType('Item')),
			TrinaryLogic::createNo(),
		];

		yield [
			new IntersectionType([
				new ObjectType(Traversable::class),
				new IterableType(new MixedType(true), new ObjectType(stdClass::class)),
			]),
			new IntersectionType([
				new ObjectType(Traversable::class),
				new IterableType(new MixedType(true), new ObjectType(stdClass::class)),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new IntersectionType([
				new ObjectType(Collection::class),
				new IterableType(new MixedType(true), new ObjectType(stdClass::class)),
			]),
			new IntersectionType([
				new ObjectType(Collection::class),
				new IterableType(new MixedType(true), new ObjectType(stdClass::class)),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new IntersectionType([
				new ObjectType(\TestIntersectionTypeIsSupertypeOf\Collection::class),
				new IterableType(new MixedType(true), new ObjectType(stdClass::class)),
			]),
			new IntersectionType([
				new ObjectType(\TestIntersectionTypeIsSupertypeOf\Collection::class),
				new IterableType(new MixedType(true), new ObjectType(stdClass::class)),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new IntersectionType([
				new ObjectType(Collection::class),
				new IterableType(new MixedType(true), new ObjectType(stdClass::class)),
			]),
			new IntersectionType([
				new ObjectType(Collection::class),
				new IterableType(new MixedType(), new ObjectType(stdClass::class)),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new IntersectionType([
				new ObjectType(Collection::class),
				new IterableType(new MixedType(), new ObjectType(stdClass::class)),
			]),
			new IntersectionType([
				new ObjectType(Collection::class),
				new IterableType(new MixedType(true), new ObjectType(stdClass::class)),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new IntersectionType([
				new ObjectType(Collection::class),
				new IterableType(new MixedType(), new ObjectType(stdClass::class)),
			]),
			new IntersectionType([
				new ObjectType(Collection::class),
				new IterableType(new MixedType(), new ObjectType(stdClass::class)),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new IntersectionType([new ArrayType(new IntegerType(), new StringType()), new OversizedArrayType()]),
			new ArrayType(new IntegerType(), new StringType()),
			TrinaryLogic::createMaybe(),
		];
	}

	#[DataProvider('dataIsSuperTypeOf')]
	public function testIsSuperTypeOf(IntersectionType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataIsSubTypeOf(): Iterator
	{
		$intersectionTypeA = new IntersectionType([
			new ObjectType('ArrayObject'),
			new IterableType(new MixedType(), new ObjectType('Item')),
		]);

		yield [
			$intersectionTypeA,
			$intersectionTypeA,
			TrinaryLogic::createYes(),
		];

		yield [
			$intersectionTypeA,
			new ObjectType('ArrayObject'),
			TrinaryLogic::createYes(),
		];

		yield [
			$intersectionTypeA,
			new IterableType(new MixedType(), new ObjectType('Item')),
			TrinaryLogic::createYes(),
		];

		yield [
			$intersectionTypeA,
			new MixedType(),
			TrinaryLogic::createYes(),
		];

		yield [
			$intersectionTypeA,
			new IterableType(new MixedType(), new ObjectType('Unknown')),
			TrinaryLogic::createMaybe(),
		];

		yield [
			$intersectionTypeA,
			new ArrayType(new MixedType(), new ObjectType('Item')),
			TrinaryLogic::createNo(),
		];

		$intersectionTypeC = new IntersectionType([
			new StringType(),
			new CallableType(),
		]);

		yield [
			$intersectionTypeC,
			$intersectionTypeC,
			TrinaryLogic::createYes(),
		];

		yield [
			$intersectionTypeC,
			new StringType(),
			TrinaryLogic::createYes(),
		];

		yield [
			$intersectionTypeC,
			new UnionType([new IntegerType(), new StringType()]),
			TrinaryLogic::createYes(),
		];

		$intersectionTypeD = new IntersectionType([
			new ObjectType('ArrayObject'),
			new IterableType(new MixedType(), new ObjectType('DatePeriod')),
		]);

		yield [
			$intersectionTypeD,
			$intersectionTypeD,
			TrinaryLogic::createYes(),
		];

		yield [
			$intersectionTypeD,
			new UnionType([
				$intersectionTypeD,
				new IntegerType(),
			]),
			TrinaryLogic::createYes(),
		];
	}

	#[DataProvider('dataIsSubTypeOf')]
	public function testIsSubTypeOf(IntersectionType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSubTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSubTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	#[DataProvider('dataIsSubTypeOf')]
	public function testIsSubTypeOfInversed(IntersectionType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $otherType->isSuperTypeOf($type);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $otherType->describe(VerbosityLevel::precise()), $type->describe(VerbosityLevel::precise())),
		);
	}

	public function testToBooleanCrash(): void
	{
		$type = new IntersectionType([new NeverType(), new NonEmptyArrayType()]);
		$this->assertSame('true', $type->toBoolean()->describe(VerbosityLevel::precise()));
	}

	public static function dataGetEnumCases(): iterable
	{
		if (PHP_VERSION_ID < 80100) {
			return [];
		}

		$reflectionProvider = self::createReflectionProvider();
		$classReflection = $reflectionProvider->getClass(FooEnum::class);

		yield [
			new IntersectionType([
				new ThisType($classReflection),
				new EnumCaseObjectType(FooEnum::class, 'FOO'),
			]),
			[
				new EnumCaseObjectType(FooEnum::class, 'FOO'),
			],
		];
	}

	/**
	 * @param list<EnumCaseObjectType> $expectedEnumCases
	 */
	#[DataProvider('dataGetEnumCases')]
	public function testGetEnumCases(
		IntersectionType $type,
		array $expectedEnumCases,
	): void
	{
		$enumCases = $type->getEnumCases();
		$this->assertCount(count($expectedEnumCases), $enumCases);
		foreach ($enumCases as $i => $enumCase) {
			$expectedEnumCase = $expectedEnumCases[$i];
			$this->assertTrue($expectedEnumCase->equals($enumCase), sprintf('%s->equals(%s)', $expectedEnumCase->describe(VerbosityLevel::precise()), $enumCase->describe(VerbosityLevel::precise())));
		}
	}

	public static function dataDescribe(): iterable
	{
		yield [
			new IntersectionType([new StringType(), new AccessoryLowercaseStringType()]),
			VerbosityLevel::typeOnly(),
			'string',
		];
		yield [
			new IntersectionType([new StringType(), new AccessoryLowercaseStringType()]),
			VerbosityLevel::value(),
			'string',
		];
		yield [
			new IntersectionType([new StringType(), new AccessoryLowercaseStringType()]),
			VerbosityLevel::precise(),
			'lowercase-string',
		];
		yield [
			new IntersectionType([
				new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new MixedType()),
				new AccessoryArrayListType(),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::typeOnly(),
			'list',
		];
		yield [
			new IntersectionType([
				new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new MixedType()),
				new AccessoryArrayListType(),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::value(),
			'non-empty-list',
		];

		yield [
			new IntersectionType([
				new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new IntegerType()),
				new AccessoryArrayListType(),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::typeOnly(),
			'list<int>',
		];
		yield [
			new IntersectionType([
				new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new IntegerType()),
				new AccessoryArrayListType(),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::value(),
			'non-empty-list<int>',
		];

		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType()),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::typeOnly(),
			'array',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType()),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::value(),
			'non-empty-array',
		];

		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new IntegerType()),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::typeOnly(),
			'array<int>',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new IntegerType()),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::value(),
			'non-empty-array<int>',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new IntegerType()),
				new AccessoryArrayListType(),
			]),
			VerbosityLevel::typeOnly(),
			'list<int>',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new IntegerType()),
				new AccessoryArrayListType(),
			]),
			VerbosityLevel::value(),
			'list<int>',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType()),
				new AccessoryArrayListType(),
			]),
			VerbosityLevel::typeOnly(),
			'list',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType()),
				new AccessoryArrayListType(),
			]),
			VerbosityLevel::value(),
			'list',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType(true)),
				new AccessoryArrayListType(),
			]),
			VerbosityLevel::typeOnly(),
			'list<mixed>',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType(true)),
				new AccessoryArrayListType(),
			]),
			VerbosityLevel::value(),
			'list<mixed>',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType()),
				new AccessoryArrayListType(),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::typeOnly(),
			'list',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType()),
				new AccessoryArrayListType(),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::value(),
			'non-empty-list',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType(true)),
				new AccessoryArrayListType(),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::typeOnly(),
			'list<mixed>',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType(true)),
				new AccessoryArrayListType(),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::value(),
			'non-empty-list<mixed>',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType()),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::typeOnly(),
			'array',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType()),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::value(),
			'non-empty-array',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType(true)),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::typeOnly(),
			'array<mixed>',
		];
		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType(true)),
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::value(),
			'non-empty-array<mixed>',
		];

		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType(true)),
				new NonEmptyArrayType(),
				new OversizedArrayType(),
			]),
			VerbosityLevel::value(),
			'non-empty-array<mixed>',
		];

		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType(true)),
				new NonEmptyArrayType(),
				new OversizedArrayType(),
			]),
			VerbosityLevel::precise(),
			'non-empty-array<mixed>&oversized-array',
		];

		$constantArrayWithOptionalKeys = new ConstantArrayType([
			new ConstantIntegerType(0),
			new ConstantIntegerType(1),
			new ConstantIntegerType(2),
			new ConstantIntegerType(3),
		], [
			new StringType(),
			new StringType(),
			new StringType(),
			new StringType(),
		], [3], [2, 3], TrinaryLogic::createMaybe());

		yield [
			new IntersectionType([
				$constantArrayWithOptionalKeys,
				new AccessoryArrayListType(),
			]),
			VerbosityLevel::typeOnly(),
			'list<string>',
		];

		yield [
			new IntersectionType([
				$constantArrayWithOptionalKeys,
				new AccessoryArrayListType(),
			]),
			VerbosityLevel::value(),
			'list{0: string, 1: string, 2?: string, 3?: string}',
		];

		yield [
			new IntersectionType([
				$constantArrayWithOptionalKeys,
				new AccessoryArrayListType(),
			]),
			VerbosityLevel::precise(),
			'list{0: string, 1: string, 2?: string, 3?: string}',
		];

		$constantArrayWithAllOptionalKeys = new ConstantArrayType([
			new ConstantIntegerType(0),
			new ConstantIntegerType(1),
			new ConstantIntegerType(2),
			new ConstantIntegerType(3),
		], [
			new StringType(),
			new StringType(),
			new StringType(),
			new StringType(),
		], [3], [0, 1, 2, 3], TrinaryLogic::createMaybe());

		yield [
			new IntersectionType([
				$constantArrayWithAllOptionalKeys,
				new AccessoryArrayListType(),
			]),
			VerbosityLevel::value(),
			'list{0?: string, 1?: string, 2?: string, 3?: string}',
		];

		yield [
			new IntersectionType([
				$constantArrayWithAllOptionalKeys,
				new NonEmptyArrayType(),
				new AccessoryArrayListType(),
			]),
			VerbosityLevel::value(),
			'non-empty-list{0?: string, 1?: string, 2?: string, 3?: string}',
		];

		yield [
			new IntersectionType([
				$constantArrayWithAllOptionalKeys,
				new NonEmptyArrayType(),
			]),
			VerbosityLevel::value(),
			'non-empty-array{0?: string, 1?: string, 2?: string, 3?: string}',
		];
		yield [
			new IntersectionType([new StringType(), new AccessoryUppercaseStringType()]),
			VerbosityLevel::typeOnly(),
			'string',
		];
		yield [
			new IntersectionType([new StringType(), new AccessoryUppercaseStringType()]),
			VerbosityLevel::value(),
			'string',
		];
		yield [
			new IntersectionType([new StringType(), new AccessoryUppercaseStringType()]),
			VerbosityLevel::precise(),
			'uppercase-string',
		];
	}

	#[DataProvider('dataDescribe')]
	public function testDescribe(IntersectionType $type, VerbosityLevel $verbosityLevel, string $expected): void
	{
		static::assertSame($expected, $type->describe($verbosityLevel));
	}

}
