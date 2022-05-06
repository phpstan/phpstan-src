<?php declare(strict_types = 1);

namespace PHPStan\Type;

use DoctrineIntersectionTypeIsSupertypeOf\Collection;
use Iterator;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use stdClass;
use Test\ClassWithToString;
use Traversable;
use function sprintf;

class IntersectionTypeTest extends PHPStanTestCase
{

	public function dataAccepts(): Iterator
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
			TrinaryLogic::createNo(),
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
			TrinaryLogic::createNo(),
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(IntersectionType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsCallable(): array
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

	/**
	 * @dataProvider dataIsCallable
	 */
	public function testIsCallable(IntersectionType $type, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isCallable();
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isCallable()', $type->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsSuperTypeOf(): Iterator
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
				new ArrayType(new MixedType(), new MixedType()),
				new HasOffsetType(new StringType()),
			]),
			new ConstantArrayType([
				new ConstantStringType('a'),
				new ConstantStringType('b'),
				new ConstantStringType('c'),
			], [
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
				new ConstantIntegerType(3),
			]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new IntersectionType([
				new ArrayType(new MixedType(), new MixedType()),
				new HasOffsetType(new StringType()),
			]),
			new ConstantArrayType([
				new ConstantStringType('a'),
				new ConstantStringType('b'),
				new ConstantStringType('c'),
				new ConstantStringType('d'),
				new ConstantStringType('e'),
				new ConstantStringType('f'),
				new ConstantStringType('g'),
				new ConstantStringType('h'),
				new ConstantStringType('i'),
			], [
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
				new ConstantIntegerType(3),
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
				new ConstantIntegerType(3),
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
				new ConstantIntegerType(3),
			]),
			TrinaryLogic::createMaybe(),
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
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(IntersectionType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsSubTypeOf(): Iterator
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

	/**
	 * @dataProvider dataIsSubTypeOf
	 */
	public function testIsSubTypeOf(IntersectionType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSubTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSubTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	/**
	 * @dataProvider dataIsSubTypeOf
	 */
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

}
