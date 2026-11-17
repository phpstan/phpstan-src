<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;

class ConditionalTypeForParameterTest extends PHPStanTestCase
{

	/**
	 * @return iterable<string, array{ConditionalTypeForParameter, Type, TrinaryLogic}>
	 */
	public static function dataIsSuperTypeOf(): iterable
	{
		$type = new ConditionalTypeForParameter('$x', new IntegerType(), new IntegerType(), new StringType(), false);

		yield 'same condition' => [
			$type,
			new ConditionalTypeForParameter('$x', new IntegerType(), new IntegerType(), new StringType(), false),
			TrinaryLogic::createYes(),
		];

		yield 'same condition, narrower branches' => [
			$type,
			new ConditionalTypeForParameter('$x', new IntegerType(), new ConstantIntegerType(1), new StringType(), false),
			TrinaryLogic::createYes(),
		];

		yield 'different negation' => [
			$type,
			new ConditionalTypeForParameter('$x', new IntegerType(), new IntegerType(), new StringType(), true),
			TrinaryLogic::createMaybe(),
		];

		yield 'different parameter name' => [
			$type,
			new ConditionalTypeForParameter('$y', new IntegerType(), new IntegerType(), new StringType(), false),
			TrinaryLogic::createMaybe(),
		];

		yield 'different target' => [
			$type,
			new ConditionalTypeForParameter('$x', new StringType(), new IntegerType(), new StringType(), false),
			TrinaryLogic::createMaybe(),
		];
	}

	#[DataProvider('dataIsSuperTypeOf')]
	public function testIsSuperTypeOf(ConditionalTypeForParameter $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	/**
	 * @return iterable<string, array{ConditionalTypeForParameter, Type, bool}>
	 */
	public static function dataEquals(): iterable
	{
		$type = new ConditionalTypeForParameter('$x', new IntegerType(), new IntegerType(), new StringType(), false);

		yield 'same' => [
			$type,
			new ConditionalTypeForParameter('$x', new IntegerType(), new IntegerType(), new StringType(), false),
			true,
		];

		yield 'different negation' => [
			$type,
			new ConditionalTypeForParameter('$x', new IntegerType(), new IntegerType(), new StringType(), true),
			false,
		];

		yield 'different parameter name' => [
			$type,
			new ConditionalTypeForParameter('$y', new IntegerType(), new IntegerType(), new StringType(), false),
			false,
		];

		yield 'different target' => [
			$type,
			new ConditionalTypeForParameter('$x', new StringType(), new IntegerType(), new StringType(), false),
			false,
		];

		yield 'different if' => [
			$type,
			new ConditionalTypeForParameter('$x', new IntegerType(), new StringType(), new StringType(), false),
			false,
		];

		yield 'different else' => [
			$type,
			new ConditionalTypeForParameter('$x', new IntegerType(), new IntegerType(), new IntegerType(), false),
			false,
		];
	}

	#[DataProvider('dataEquals')]
	public function testEquals(ConditionalTypeForParameter $type, Type $otherType, bool $expected): void
	{
		$this->assertSame($expected, $type->equals($otherType));
		$this->assertSame($expected, $otherType->equals($type));
	}

}
