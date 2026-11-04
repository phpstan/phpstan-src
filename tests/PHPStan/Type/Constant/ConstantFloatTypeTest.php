<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\FloatRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;
use const INF;
use const NAN;

class ConstantFloatTypeTest extends PHPStanTestCase
{

	public static function dataDescribe(): array
	{
		return [
			[
				new ConstantFloatType(2.0),
				'2.0',
			],
			[
				new ConstantFloatType(2.0123),
				'2.0123',
			],
			[
				new ConstantFloatType(1.2000000992884E-10),
				'1.2000000992884E-10',
			],
			[
				new ConstantFloatType(-1.200000099288476E+10),
				'-12000000992.88476',
			],
			[
				new ConstantFloatType(-1.200000099288476E+20),
				'-1.200000099288476E+20',
			],
			[
				new ConstantFloatType(1.2 * 1.4),
				'1.68',
			],
		];
	}

	#[DataProvider('dataDescribe')]
	public function testDescribe(
		ConstantFloatType $type,
		string $expectedDescription,
	): void
	{
		$this->assertSame($expectedDescription, $type->describe(VerbosityLevel::precise()));
	}

	public static function dataAccepts(): iterable
	{
		$range = FloatRangeType::fromInterval(1.0, 2.0);

		yield [new ConstantFloatType(1.5), $range, TrinaryLogic::createMaybe()];
		yield [new ConstantFloatType(1.0), $range, TrinaryLogic::createMaybe()];
		yield [new ConstantFloatType(5.0), $range, TrinaryLogic::createNo()];
		yield [new ConstantFloatType(NAN), $range, TrinaryLogic::createNo()];
		yield [new ConstantFloatType(INF), $range, TrinaryLogic::createNo()];
		yield [new ConstantFloatType(INF), FloatRangeType::fromInterval(1.0, INF), TrinaryLogic::createMaybe()];
		yield [new ConstantFloatType(-0.0), FloatRangeType::fromInterval(0.0, 1.0), TrinaryLogic::createMaybe()];
		yield [new ConstantFloatType(5.0), TypeCombinator::union($range, new ConstantFloatType(5.0)), TrinaryLogic::createMaybe()];
		yield [new ConstantFloatType(5.0), TypeCombinator::union($range, FloatRangeType::fromInterval(3.0, 4.0)), TrinaryLogic::createNo()];
	}

	#[DataProvider('dataAccepts')]
	public function testAccepts(ConstantFloatType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$this->assertSame(
			$expectedResult->describe(),
			$type->accepts($otherType, true)->result->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
		$this->assertSame(
			$expectedResult->describe(),
			$type->isSuperTypeOf($otherType)->result->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

}
