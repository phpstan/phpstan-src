<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantIntegerType;

class IntegerRangeTypeTest extends \PHPStan\Testing\TestCase
{

	public function dataIsSubTypeOf(): iterable
	{
		yield [
			IntegerRangeType::fromInterval(5, 10),
			new IntegerType(),
			TrinaryLogic::createYes(),
		];

		yield [
			IntegerRangeType::fromInterval(5, 10),
			new ConstantIntegerType(10),
			TrinaryLogic::createMaybe(),
		];

		yield [
			IntegerRangeType::fromInterval(5, 10),
			new ConstantIntegerType(20),
			TrinaryLogic::createNo(),
		];

		yield [
			IntegerRangeType::fromInterval(5, 10),
			IntegerRangeType::fromInterval(0, 15),
			TrinaryLogic::createYes(),
		];

		yield [
			IntegerRangeType::fromInterval(5, 10),
			IntegerRangeType::fromInterval(6, 9),
			TrinaryLogic::createMaybe(),
		];

		yield [
			IntegerRangeType::fromInterval(5, 10),
			new UnionType([new IntegerType(), new StringType()]),
			TrinaryLogic::createYes(),
		];

		yield [
			IntegerRangeType::fromInterval(5, 10),
			new UnionType([new ConstantIntegerType(5), IntegerRangeType::fromInterval(6, 10), new StringType()]),
			TrinaryLogic::createYes(),
		];

		yield [
			IntegerRangeType::fromInterval(5, 10),
			new StringType(),
			TrinaryLogic::createNo(),
		];
	}

	/**
	 * @dataProvider dataIsSubTypeOf
	 */
	public function testIsSubTypeOf(IntegerRangeType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSubTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

}
