<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\TestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantStringType;

class CallableStringTypeTest extends TestCase
{

	public function dataIsSuperTypeOf(): iterable
	{
		yield [
			new CallableStringType(),
			new UnionType([new ConstantStringType('test_function'), new ConstantStringType('test_function_2')]),
			TrinaryLogic::createYes(),
		];

		yield [
			new CallableStringType(),
			new UnionType([new ConstantStringType('test_function'), new StringType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new CallableStringType(),
			new UnionType([new IntegerType(), new FloatType()]),
			TrinaryLogic::createNo(),
		];

		yield [
			new CallableStringType(),
			new ConstantStringType('test_function'),
			TrinaryLogic::createYes(),
		];

		yield [
			new CallableStringType(),
			new CallableStringType(),
			TrinaryLogic::createYes(),
		];

		yield [
			new CallableStringType(),
			new StringType(),
			TrinaryLogic::createMaybe(),
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(CallableStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

	public function dataAccepts(): iterable
	{
		yield [
			new CallableStringType(),
			new CallableStringType(),
			TrinaryLogic::createYes(),
		];

		yield [
			new CallableStringType(),
			new StringType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new CallableStringType(),
			new IntegerType(),
			TrinaryLogic::createNo(),
		];

		yield [
			new CallableStringType(),
			new ConstantStringType('test_function'),
			TrinaryLogic::createYes(),
		];

		yield [
			new CallableStringType(),
			new UnionType([new ConstantStringType('test_function'), new ConstantStringType('test_function_2')]),
			TrinaryLogic::createYes(),
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(CallableStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

}
