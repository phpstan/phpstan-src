<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;

class BooleanTypeTest extends PHPStanTestCase
{

	public static function dataAccepts(): array
	{
		return [
			[
				new BooleanType(),
				new BooleanType(),
				TrinaryLogic::createYes(),
			],
			[
				new BooleanType(),
				new ConstantBooleanType(true),
				TrinaryLogic::createYes(),
			],
			[
				new BooleanType(),
				new NullType(),
				TrinaryLogic::createNo(),
			],
			[
				new BooleanType(),
				new MixedType(),
				TrinaryLogic::createYes(),
			],
			[
				new BooleanType(),
				new FloatType(),
				TrinaryLogic::createNo(),
			],
			[
				new BooleanType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
		];
	}

	#[DataProvider('dataAccepts')]
	public function testAccepts(BooleanType $type, Type $otherType, TrinaryLogic $expectedResult): void
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
			new BooleanType(),
			new BooleanType(),
			TrinaryLogic::createYes(),
		];

		yield [
			new BooleanType(),
			new ConstantBooleanType(true),
			TrinaryLogic::createYes(),
		];

		yield [
			new BooleanType(),
			new MixedType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new BooleanType(),
			new UnionType([new BooleanType(), new StringType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new BooleanType(),
			new StringType(),
			TrinaryLogic::createNo(),
		];
	}

	#[DataProvider('dataIsSuperTypeOf')]
	public function testIsSuperTypeOf(BooleanType $type, Type $otherType, TrinaryLogic $expectedResult): void
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
				new BooleanType(),
				new BooleanType(),
				true,
			],
			[
				new ConstantBooleanType(false),
				new ConstantBooleanType(false),
				true,
			],
			[
				new ConstantBooleanType(true),
				new ConstantBooleanType(false),
				false,
			],
			[
				new BooleanType(),
				new ConstantBooleanType(false),
				false,
			],
			[
				new ConstantBooleanType(false),
				new BooleanType(),
				false,
			],
			[
				new BooleanType(),
				new IntegerType(),
				false,
			],
			[
				new ConstantBooleanType(false),
				new ConstantIntegerType(0),
				false,
			],
		];
	}

	#[DataProvider('dataEquals')]
	public function testEquals(BooleanType $type, Type $otherType, bool $expectedResult): void
	{
		$actualResult = $type->equals($otherType);
		$this->assertSame(
			$expectedResult,
			$actualResult,
			sprintf('%s->equals(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

}
