<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;
use const INF;
use const NAN;

class FloatRangeTypeTest extends PHPStanTestCase
{

	public static function dataAccepts(): iterable
	{
		$range = FloatRangeType::fromInterval(1.0, 2.0);

		// float ranges
		yield [$range, FloatRangeType::fromInterval(1.0, 2.0), TrinaryLogic::createYes()];
		yield [$range, FloatRangeType::fromInterval(1.25, 1.5), TrinaryLogic::createYes()];
		yield [$range, FloatRangeType::fromInterval(1.5, 3.0), TrinaryLogic::createMaybe()];
		yield [$range, FloatRangeType::fromInterval(3.0, 4.0), TrinaryLogic::createNo()];
		yield [$range, FloatRangeType::fromInterval(-INF, INF), TrinaryLogic::createMaybe()];
		yield [FloatRangeType::fromInterval(-INF, INF), FloatRangeType::fromInterval(1.0, INF), TrinaryLogic::createYes()];

		// constant floats
		yield [$range, new ConstantFloatType(1.5), TrinaryLogic::createYes()];
		yield [$range, new ConstantFloatType(2.0), TrinaryLogic::createYes()];
		yield [$range, new ConstantFloatType(3.0), TrinaryLogic::createNo()];
		yield [$range, new ConstantFloatType(NAN), TrinaryLogic::createNo()];
		yield [$range, new ConstantFloatType(INF), TrinaryLogic::createNo()];
		yield [FloatRangeType::fromInterval(1.0, INF), new ConstantFloatType(INF), TrinaryLogic::createYes()];
		yield [FloatRangeType::fromInterval(-1.0, 0.0), new ConstantFloatType(-0.0), TrinaryLogic::createYes()];

		// general float, NAN is in no range
		yield [$range, new FloatType(), TrinaryLogic::createMaybe()];
		yield [FloatRangeType::fromInterval(-INF, INF), new FloatType(), TrinaryLogic::createMaybe()];

		// integers coerce to floats
		yield [$range, new IntegerType(), TrinaryLogic::createMaybe()];
		yield [FloatRangeType::fromInterval(-INF, INF), new IntegerType(), TrinaryLogic::createYes()];
		yield [$range, IntegerRangeType::fromInterval(1, 2), TrinaryLogic::createYes()];
		yield [$range, IntegerRangeType::fromInterval(0, 5), TrinaryLogic::createMaybe()];
		yield [$range, IntegerRangeType::fromInterval(3, 5), TrinaryLogic::createNo()];
		yield [$range, IntegerRangeType::fromInterval(1, null), TrinaryLogic::createMaybe()];
		yield [$range, new ConstantIntegerType(1), TrinaryLogic::createYes()];
		yield [$range, new ConstantIntegerType(3), TrinaryLogic::createNo()];

		// unions
		yield [$range, TypeCombinator::union(new ConstantFloatType(1.0), new ConstantFloatType(1.5)), TrinaryLogic::createYes()];
		yield [$range, TypeCombinator::union(new ConstantFloatType(1.0), new ConstantFloatType(3.0)), TrinaryLogic::createMaybe()];
		yield [$range, TypeCombinator::union(IntegerRangeType::fromInterval(1, 2), FloatRangeType::fromInterval(1.5, 2.0)), TrinaryLogic::createYes()];
		yield [$range, TypeCombinator::addNull($range), TrinaryLogic::createMaybe()];

		// other types
		yield [$range, new MixedType(), TrinaryLogic::createYes()];
		yield [$range, new NullType(), TrinaryLogic::createNo()];
		yield [$range, new StringType(), TrinaryLogic::createNo()];
		yield [$range, new ConstantBooleanType(true), TrinaryLogic::createNo()];
	}

	#[DataProvider('dataAccepts')]
	public function testAccepts(Type $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true)->result;
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataAcceptsReasons(): iterable
	{
		$reason = 'NAN is in no float range. Use float<min, max>|NAN to accept it.';
		yield [FloatRangeType::fromInterval(-INF, INF), new ConstantFloatType(NAN), [$reason]];
		yield [FloatRangeType::fromInterval(-INF, INF), new FloatType(), [$reason]];
		yield [FloatRangeType::fromInterval(1.0, 2.0), TypeCombinator::union(new ConstantFloatType(1.5), new ConstantFloatType(NAN)), ['NAN is in no float range. Use float<1.0, 2.0>|NAN to accept it.']];

		// other values are out of the range too
		yield [FloatRangeType::fromInterval(1.0, 2.0), new FloatType(), []];
		yield [FloatRangeType::fromInterval(1.0, 2.0), new ConstantFloatType(3.0), []];
	}

	/**
	 * @param list<string> $expectedReasons
	 */
	#[DataProvider('dataAcceptsReasons')]
	public function testAcceptsReasons(Type $type, Type $otherType, array $expectedReasons): void
	{
		$this->assertSame($expectedReasons, $type->accepts($otherType, true)->reasons);
	}

	public static function dataIsSuperTypeOf(): iterable
	{
		$range = FloatRangeType::fromInterval(1.0, 2.0);

		yield [$range, FloatRangeType::fromInterval(1.25, 1.5), TrinaryLogic::createYes()];
		yield [$range, FloatRangeType::fromInterval(1.5, 3.0), TrinaryLogic::createMaybe()];
		yield [$range, FloatRangeType::fromInterval(3.0, 4.0), TrinaryLogic::createNo()];
		yield [$range, new ConstantFloatType(1.5), TrinaryLogic::createYes()];
		yield [$range, new ConstantFloatType(3.0), TrinaryLogic::createNo()];
		yield [$range, new ConstantFloatType(NAN), TrinaryLogic::createNo()];
		yield [$range, new FloatType(), TrinaryLogic::createMaybe()];

		// integers are not floats
		yield [$range, new IntegerType(), TrinaryLogic::createNo()];
		yield [$range, IntegerRangeType::fromInterval(1, 2), TrinaryLogic::createNo()];
		yield [$range, new ConstantIntegerType(1), TrinaryLogic::createNo()];

		yield [$range, new MixedType(), TrinaryLogic::createMaybe()];
		yield [$range, TypeCombinator::union(new ConstantFloatType(1.0), new ConstantFloatType(1.5)), TrinaryLogic::createYes()];
		yield [$range, TypeCombinator::union(new ConstantFloatType(1.0), new IntegerType()), TrinaryLogic::createMaybe()];
		yield [$range, TypeCombinator::addNull($range), TrinaryLogic::createMaybe()];
	}

	#[DataProvider('dataIsSuperTypeOf')]
	public function testIsSuperTypeOf(Type $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType)->result;
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataIsSubTypeOf(): iterable
	{
		$range = FloatRangeType::fromInterval(1.0, 2.0);

		// the other side asks the range back
		yield [new ConstantFloatType(1.5), $range, TrinaryLogic::createMaybe()];
		yield [new ConstantFloatType(5.0), $range, TrinaryLogic::createNo()];
		yield [new ConstantFloatType(NAN), $range, TrinaryLogic::createNo()];
		yield [new FloatType(), $range, TrinaryLogic::createYes()];
		yield [new IntegerType(), $range, TrinaryLogic::createNo()];
		yield [TypeCombinator::union(new ConstantFloatType(1.0), new ConstantFloatType(5.0)), $range, TrinaryLogic::createMaybe()];
		yield [TypeCombinator::union(FloatRangeType::fromInterval(0.0, 1.5), FloatRangeType::fromInterval(1.75, 3.0)), $range, TrinaryLogic::createMaybe()];
		yield [TypeCombinator::union(new IntegerType(), FloatRangeType::fromInterval(0.0, 3.0)), $range, TrinaryLogic::createYes()];
		yield [TypeCombinator::addNull(new FloatType()), $range, TrinaryLogic::createYes()];
	}

	#[DataProvider('dataIsSubTypeOf')]
	public function testIsSubTypeOf(Type $superType, Type $range, TrinaryLogic $expectedResult): void
	{
		$this->assertSame(
			$expectedResult->describe(),
			$superType->isSuperTypeOf($range)->result->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $superType->describe(VerbosityLevel::precise()), $range->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataComparison(): iterable
	{
		$range = FloatRangeType::fromInterval(1.0, 2.0);

		// other < range
		yield ['<', new ConstantIntegerType(0), $range, TrinaryLogic::createYes()];
		yield ['<', new ConstantFloatType(0.5), $range, TrinaryLogic::createYes()];
		yield ['<', new ConstantFloatType(1.5), $range, TrinaryLogic::createMaybe()];
		yield ['<', new ConstantFloatType(2.0), $range, TrinaryLogic::createNo()];
		yield ['<', IntegerRangeType::fromInterval(3, 5), $range, TrinaryLogic::createNo()];
		yield ['<', IntegerRangeType::fromInterval(null, 0), $range, TrinaryLogic::createYes()];
		yield ['<', new NullType(), $range, TrinaryLogic::createYes()];
		yield ['<', new ConstantBooleanType(false), $range, TrinaryLogic::createYes()];
		yield ['<', new ConstantBooleanType(true), $range, TrinaryLogic::createNo()];
		yield ['<', new ConstantFloatType(NAN), $range, TrinaryLogic::createNo()];
		yield ['<', new FloatType(), $range, TrinaryLogic::createMaybe()];

		// other <= range
		yield ['<=', new ConstantFloatType(1.0), $range, TrinaryLogic::createYes()];
		yield ['<=', new ConstantIntegerType(2), $range, TrinaryLogic::createMaybe()];
		yield ['<=', new ConstantIntegerType(3), $range, TrinaryLogic::createNo()];
		yield ['<=', new ConstantBooleanType(true), $range, TrinaryLogic::createYes()];

		// range < other
		yield ['<', $range, new ConstantIntegerType(3), TrinaryLogic::createYes()];
		yield ['<', $range, new ConstantFloatType(2.0), TrinaryLogic::createMaybe()];
		yield ['<', $range, new ConstantFloatType(1.0), TrinaryLogic::createNo()];
		yield ['<', $range, new NullType(), TrinaryLogic::createNo()];
		yield ['<=', $range, new ConstantFloatType(2.0), TrinaryLogic::createYes()];

		// ranges that hold zero compare with null and booleans by truthiness
		$withZero = FloatRangeType::fromInterval(-1.0, 1.0);
		yield ['<', new NullType(), $withZero, TrinaryLogic::createMaybe()];
		yield ['<=', new NullType(), $withZero, TrinaryLogic::createYes()];
	}

	#[DataProvider('dataComparison')]
	public function testComparison(string $operator, Type $left, Type $right, TrinaryLogic $expectedResult): void
	{
		$phpVersion = new PhpVersion(80400);
		$actualResult = $operator === '<'
			? $left->isSmallerThan($right, $phpVersion)
			: $left->isSmallerThanOrEqual($right, $phpVersion);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s %s %s', $left->describe(VerbosityLevel::precise()), $operator, $right->describe(VerbosityLevel::precise())),
		);
	}

	public function testDescribe(): void
	{
		$range = FloatRangeType::fromInterval(-INF, 1.5);
		$this->assertSame('float', $range->describe(VerbosityLevel::typeOnly()));
		$this->assertSame('float<min, 1.5>', $range->describe(VerbosityLevel::value()));
		$this->assertSame('float<min, 1.5>', $range->describe(VerbosityLevel::precise()));
		$this->assertSame('float<min, 1.5>', $range->describe(VerbosityLevel::cache()));
	}

}
