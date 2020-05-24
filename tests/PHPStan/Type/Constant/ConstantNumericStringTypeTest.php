<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\TrinaryLogic;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class ConstantNumericStringTypeTest extends \PHPStan\Testing\TestCase
{

	public function dataIsSuperTypeOf(): iterable
	{
		yield [
			new ConstantNumericStringType(1),
			new ConstantNumericStringType(1),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantNumericStringType(1),
			new ConstantIntegerType(1),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantNumericStringType(2),
			new ConstantStringType('2'),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantNumericStringType(1),
			new IntegerType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ConstantNumericStringType(1),
			new StringType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ConstantNumericStringType(1),
			new ConstantNumericStringType(2),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantNumericStringType(1),
			new ConstantIntegerType(2),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantNumericStringType(1),
			new ConstantStringType('2'),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantNumericStringType(0),
			new ConstantStringType('string'),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantNumericStringType(1234),
			new ConstantStringType('01234'),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantNumericStringType(0),
			new ConstantStringType(''),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantNumericStringType(1),
			new ConstantBooleanType(true),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantNumericStringType(0),
			new ConstantBooleanType(false),
			TrinaryLogic::createNo(),
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 * @param ConstantNumericStringType $type
	 * @param Type $otherType
	 * @param TrinaryLogic $expectedResult
	 */
	public function testIsSuperTypeOf(ConstantNumericStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

}
