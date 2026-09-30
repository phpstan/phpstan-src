<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use function restore_error_handler;
use function set_error_handler;
use const PHP_INT_MAX;
use const PHP_INT_MIN;
use const PHP_INT_SIZE;

class IntegerRangeTypeTest extends PHPStanTestCase
{

	public static function dataFloatBounds(): iterable
	{
		$smallerThan = static fn (float $value): Type => IntegerRangeType::createAllSmallerThan($value);
		$smallerThanOrEqualTo = static fn (float $value): Type => IntegerRangeType::createAllSmallerThanOrEqualTo($value);
		$greaterThan = static fn (float $value): Type => IntegerRangeType::createAllGreaterThan($value);
		$greaterThanOrEqualTo = static fn (float $value): Type => IntegerRangeType::createAllGreaterThanOrEqualTo($value);

		// PHP_INT_MAX + 1.0 is the first float above PHP_INT_MAX on any int width, and
		// (float) PHP_INT_MIN is exact on any int width
		$aboveMax = PHP_INT_MAX + 1.0;
		yield [$smallerThan, $aboveMax, 'int'];
		yield [$smallerThanOrEqualTo, $aboveMax, 'int'];
		yield [$greaterThan, $aboveMax, '*NEVER*'];
		yield [$greaterThanOrEqualTo, $aboveMax, '*NEVER*'];
		yield [$smallerThan, (float) PHP_INT_MIN, '*NEVER*'];
		yield [$smallerThanOrEqualTo, (float) PHP_INT_MIN, (string) PHP_INT_MIN];
		yield [$greaterThan, (float) PHP_INT_MIN, 'int<' . (PHP_INT_MIN + 1) . ', max>'];
		yield [$greaterThanOrEqualTo, (float) PHP_INT_MIN, 'int'];

		// (float) PHP_INT_MAX is 2^63 on 64-bit builds (above every int) and exact on 32-bit
		// ones; so is PHP_INT_MAX - 0.5, which is PHP_INT_MAX - 1 + 0.5 on 32-bit builds
		$maxIsExact = PHP_INT_SIZE < 8;
		yield [$smallerThan, (float) PHP_INT_MAX, $maxIsExact ? 'int<min, ' . (PHP_INT_MAX - 1) . '>' : 'int'];
		yield [$smallerThanOrEqualTo, (float) PHP_INT_MAX, 'int'];
		yield [$greaterThan, (float) PHP_INT_MAX, '*NEVER*'];
		yield [$greaterThanOrEqualTo, (float) PHP_INT_MAX, $maxIsExact ? (string) PHP_INT_MAX : '*NEVER*'];
		yield [$smallerThan, PHP_INT_MAX - 0.5, $maxIsExact ? 'int<min, ' . (PHP_INT_MAX - 1) . '>' : 'int'];
		yield [$greaterThanOrEqualTo, PHP_INT_MAX - 0.5, $maxIsExact ? (string) PHP_INT_MAX : '*NEVER*'];

		// between PHP_INT_MAX and the next int on 32-bit builds, 2^63 on 64-bit ones: its
		// ceil() is past the int range either way
		yield [$smallerThan, PHP_INT_MAX + 0.5, 'int'];
		yield [$greaterThanOrEqualTo, PHP_INT_MAX + 0.5, '*NEVER*'];

		yield [$smallerThan, 2.5, 'int<min, 2>'];
		yield [$greaterThan, 2.5, 'int<3, max>'];
	}

	/**
	 * @param callable(float): Type $factory
	 */
	#[DataProvider('dataFloatBounds')]
	public function testFloatBounds(callable $factory, float $value, string $expected): void
	{
		set_error_handler(static function (int $errno, string $errstr): bool {
			throw new RuntimeException($errstr);
		});
		try {
			$this->assertSame($expected, $factory($value)->describe(VerbosityLevel::precise()));
		} finally {
			restore_error_handler();
		}
	}

}
