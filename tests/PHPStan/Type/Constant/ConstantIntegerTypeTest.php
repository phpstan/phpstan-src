<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;

class ConstantIntegerTypeTest extends PHPStanTestCase
{

	public static function dataAccepts(): iterable
	{
		yield [
			new ConstantIntegerType(1),
			new ConstantIntegerType(1),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantIntegerType(1),
			new IntegerType(),
			TrinaryLogic::createNo(),
		];

		yield [
			new ConstantIntegerType(1),
			new ConstantIntegerType(2),
			TrinaryLogic::createNo(),
		];
	}

	#[DataProvider('dataAccepts')]
	public function testAccepts(ConstantIntegerType $type, Type $otherType, TrinaryLogic $expectedResult): void
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
			new ConstantIntegerType(1),
			new ConstantIntegerType(1),
			TrinaryLogic::createYes(),
		];

		yield [
			new ConstantIntegerType(1),
			new IntegerType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new ConstantIntegerType(1),
			new ConstantIntegerType(2),
			TrinaryLogic::createNo(),
		];
	}

	#[DataProvider('dataIsSuperTypeOf')]
	public function testIsSuperTypeOf(ConstantIntegerType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

}
