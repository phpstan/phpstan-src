<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;

class FloatTypeTest extends \PHPStan\Testing\PHPStanTestCase
{

	public function dataAccepts(): array
	{
		return [
			[
				new FloatType(),
				TrinaryLogic::createYes(),
			],
			[
				new IntegerType(),
				TrinaryLogic::createYes(),
			],
			[
				new MixedType(),
				TrinaryLogic::createYes(),
			],
			[
				new UnionType([
					new IntegerType(),
					new FloatType(),
				]),
				TrinaryLogic::createYes(),
			],
			[
				new NullType(),
				TrinaryLogic::createNo(),
			],
			[
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new UnionType([
					new IntegerType(),
					new FloatType(),
					new StringType(),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new UnionType([
					new StringType(),
					new ResourceType(),
				]),
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 * @dataProvider dataAccepts
	 * @param Type $otherType
	 * @param TrinaryLogic $expectedResult
	 */
	public function testAccepts(Type $otherType, TrinaryLogic $expectedResult): void
	{
		$type = new FloatType();
		$actualResult = $type->accepts($otherType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

	public function dataEquals(): array
	{
		return [
			[
				new FloatType(),
				new FloatType(),
				true,
			],
			[
				new ConstantFloatType(0.0),
				new ConstantFloatType(0.0),
				true,
			],
			[
				new ConstantFloatType(0.0),
				new ConstantFloatType(1.0),
				false,
			],
			[
				new FloatType(),
				new ConstantFloatType(0.0),
				false,
			],
			[
				new ConstantFloatType(0.0),
				new FloatType(),
				false,
			],
			[
				new FloatType(),
				new IntegerType(),
				false,
			],
			[
				new ConstantFloatType(0.0),
				new ConstantIntegerType(0),
				false,
			],
			[
				new ConstantFloatType(0.0),
				new ConstantStringType('0.0'),
				false,
			],
		];
	}

	/**
	 * @dataProvider dataEquals
	 * @param FloatType $type
	 * @param Type $otherType
	 * @param bool $expectedResult
	 */
	public function testEquals(FloatType $type, Type $otherType, bool $expectedResult): void
	{
		$actualResult = $type->equals($otherType);
		$this->assertSame(
			$expectedResult,
			$actualResult,
			sprintf('%s->equals(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

}
