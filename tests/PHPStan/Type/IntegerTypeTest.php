<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use function sprintf;

class IntegerTypeTest extends PHPStanTestCase
{

	public static function dataAccepts(): array
	{
		return [
			[
				new IntegerType(),
				new IntegerType(),
				TrinaryLogic::createYes(),
			],
			[
				new IntegerType(),
				new ConstantIntegerType(1),
				TrinaryLogic::createYes(),
			],
			[
				new IntegerType(),
				new NullType(),
				TrinaryLogic::createNo(),
			],
			[
				new IntegerType(),
				new MixedType(),
				TrinaryLogic::createYes(),
			],
			[
				new IntegerType(),
				new FloatType(),
				TrinaryLogic::createNo(),
			],
			[
				new IntegerType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(IntegerType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true)->result;
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataIsSuperTypeOf(): iterable
	{
		yield [
			new IntegerType(),
			new IntegerType(),
			TrinaryLogic::createYes(),
		];

		yield [
			new IntegerType(),
			new ConstantIntegerType(1),
			TrinaryLogic::createYes(),
		];

		yield [
			new IntegerType(),
			new MixedType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new IntegerType(),
			new UnionType([new IntegerType(), new StringType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new IntegerType(),
			new StringType(),
			TrinaryLogic::createNo(),
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(IntegerType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataEquals(): array
	{
		return [
			[
				new IntegerType(),
				new IntegerType(),
				true,
			],
			[
				new ConstantIntegerType(0),
				new ConstantIntegerType(0),
				true,
			],
			[
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
				false,
			],
			[
				new IntegerType(),
				new ConstantIntegerType(0),
				false,
			],
			[
				new ConstantIntegerType(0),
				new IntegerType(),
				false,
			],
			[
				new IntegerType(),
				new FloatType(),
				false,
			],
			[
				new ConstantIntegerType(0),
				new ConstantFloatType(0.0),
				false,
			],
			[
				new ConstantIntegerType(0),
				new ConstantStringType('0'),
				false,
			],
		];
	}

	/**
	 * @dataProvider dataEquals
	 */
	public function testEquals(IntegerType $type, Type $otherType, bool $expectedResult): void
	{
		$actualResult = $type->equals($otherType);
		$this->assertSame(
			$expectedResult,
			$actualResult,
			sprintf('%s->equals(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

}
