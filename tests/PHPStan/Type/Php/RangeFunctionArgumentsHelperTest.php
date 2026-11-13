<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\TrinaryLogic;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RangeFunctionArgumentsHelperTest extends TestCase
{

	/**
	 * @return iterable<array{int|float|string, int|float|string, int|float, bool}>
	 */
	public static function dataIsNegativeStepOnIncreasingRange(): iterable
	{
		yield [1, 5, -1, true];
		yield [5, 1, -1, false];
		yield [5, 5, -1, false];
		yield [1, 5, 1, false];

		// two single bytes form a character range
		yield ['a', 'z', -1, true];
		yield ['z', 'a', -1, false];
		yield ['a', '5', -1, false];
		yield ['5', 'a', -1, true];
		yield ['ab', 'z', -1, true];
		yield ['a', 'z', -1.0, true];

		// PHP 8.3 keeps a step with a fractional part or beyond the integer range as a float,
		// which turns a character into 0 unless it is a digit
		yield ['a', 'z', -0.5, false];
		yield ['0', '9', -0.0001, true];
		yield ['a', '9', -0.0001, true];
		yield ['9', 'a', -0.0001, false];
		yield ['a', 'z', -1e20, false];
		yield ['a', '9', -1e20, true];

		// a numeric string with whitespace around it is a number, also on PHP 7.4
		yield ['5 ', 3, -1, false];
		yield [' 5', 3, -1, false];

		// an empty string or a character next to a number is 0
		yield ['', 'a', -1, false];
		yield ['a', '12', -1, true];
		yield ['a', 5, -1, true];
	}

	#[DataProvider('dataIsNegativeStepOnIncreasingRange')]
	public function testIsNegativeStepOnIncreasingRange(int|float|string $start, int|float|string $end, int|float $step, bool $expected): void
	{
		$this->assertSame($expected, RangeFunctionArgumentsHelper::isNegativeStepOnIncreasingRange($start, $end, $step));
	}

	/**
	 * @return iterable<array{int|float|string, int|float|string, int|float, TrinaryLogic}>
	 */
	public static function dataRejectsSincePhp83WithoutCallingRange(): iterable
	{
		yield [1, 10, 2, TrinaryLogic::createNo()];
		yield [1, 10, 20, TrinaryLogic::createYes()];

		// PHP 8.3 compares an integral float step exactly, which a float cannot do above 2 ** 53
		yield [0, 1152921504606846975, 1152921504606846976.0, TrinaryLogic::createMaybe()];
		yield [1, 9007199254740993, 9007199254740992.0, TrinaryLogic::createMaybe()];
	}

	#[DataProvider('dataRejectsSincePhp83WithoutCallingRange')]
	public function testRejectsSincePhp83WithoutCallingRange(int|float|string $start, int|float|string $end, int|float $step, TrinaryLogic $expected): void
	{
		$this->assertSame(
			$expected->describe(),
			RangeFunctionArgumentsHelper::rejects(TrinaryLogic::createYes(), $start, $end, $step, null)->describe(),
		);
	}

	/**
	 * @return iterable<array{TrinaryLogic, int|float|string, int|float|string, int|float, TrinaryLogic}>
	 */
	public static function dataRejectsAcrossPhpVersions(): iterable
	{
		// PHP 8.3 rejects a negative step on an increasing range, earlier versions ignore its sign
		yield [TrinaryLogic::createYes(), 1, 10, -1, TrinaryLogic::createYes()];
		yield [TrinaryLogic::createNo(), 1, 10, -1, TrinaryLogic::createNo()];
		yield [TrinaryLogic::createMaybe(), 1, 10, -1, TrinaryLogic::createMaybe()];

		// every PHP version agrees on these
		yield [TrinaryLogic::createMaybe(), 1, 10, 20, TrinaryLogic::createYes()];
		yield [TrinaryLogic::createMaybe(), 1, 10, 2, TrinaryLogic::createNo()];
	}

	#[DataProvider('dataRejectsAcrossPhpVersions')]
	public function testRejectsAcrossPhpVersions(TrinaryLogic $hasStricterRange, int|float|string $start, int|float|string $end, int|float $step, TrinaryLogic $expected): void
	{
		$this->assertSame(
			$expected->describe(),
			RangeFunctionArgumentsHelper::rejects($hasStricterRange, $start, $end, $step, null)->describe(),
		);
	}

}
