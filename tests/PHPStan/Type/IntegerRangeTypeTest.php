<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use const INF;
use const NAN;
use const PHP_INT_MAX;
use const PHP_INT_MIN;
use const PHP_INT_SIZE;

class IntegerRangeTypeTest extends PHPStanTestCase
{

	private const PROBED_INTS_AROUND_BOUND = 2048;

	public static function dataCreateFromFloat(): iterable
	{
		// PHP compares an int with a float as two floats, and the ints from 2**63 - 512 up convert to 2**63
		yield '2**63' => [
			9.2233720368547758E18,
			'int<min, 9223372036854775295>',
			'int',
			'*NEVER*',
			'int<9223372036854775296, max>',
		];

		// the largest float smaller than 2**63, which the ints from 2**63 - 1535 to 2**63 - 513 convert to
		yield '2**63 - 1024' => [
			9.2233720368547748E18,
			'int<min, 9223372036854774272>',
			'int<min, 9223372036854775295>',
			'int<9223372036854775296, max>',
			'int<9223372036854774273, max>',
		];

		// -2**63 is exactly PHP_INT_MIN, and the ints up to -2**63 + 512 convert to it
		yield '-2**63' => [
			-9.2233720368547758E18,
			'*NEVER*',
			'int<min, -9223372036854775296>',
			'int<-9223372036854775295, max>',
			'int',
		];

		// the largest float smaller than -2**63
		yield '-2**63 - 2048' => [
			-9.2233720368547779E18,
			'*NEVER*',
			'*NEVER*',
			'int',
			'int',
		];

		// the first float whose neighbours are 2 apart: 2**53 + 1 rounds down to it (to even)
		yield '2**53' => [
			9007199254740992.0,
			'int<min, 9007199254740991>',
			'int<min, 9007199254740993>',
			'int<9007199254740994, max>',
			'int<9007199254740992, max>',
		];

		// 2**53 + 3 and 2**53 + 5 both round to 2**53 + 4 (to even)
		yield '2**53 + 4' => [
			9007199254740996.0,
			'int<min, 9007199254740994>',
			'int<min, 9007199254740997>',
			'int<9007199254740998, max>',
			'int<9007199254740995, max>',
		];
	}

	#[DataProvider('dataCreateFromFloat')]
	public function testCreateFromFloat(
		float $value,
		string $expectedSmallerThan,
		string $expectedSmallerThanOrEqualTo,
		string $expectedGreaterThan,
		string $expectedGreaterThanOrEqualTo,
	): void
	{
		if (PHP_INT_SIZE !== 8) {
			$this->markTestSkipped('The bounds are those of a 64-bit int.');
		}

		$this->assertSame([
			'createAllSmallerThan' => $expectedSmallerThan,
			'createAllSmallerThanOrEqualTo' => $expectedSmallerThanOrEqualTo,
			'createAllGreaterThan' => $expectedGreaterThan,
			'createAllGreaterThanOrEqualTo' => $expectedGreaterThanOrEqualTo,
		], [
			'createAllSmallerThan' => IntegerRangeType::createAllSmallerThan($value)->describe(VerbosityLevel::precise()),
			'createAllSmallerThanOrEqualTo' => IntegerRangeType::createAllSmallerThanOrEqualTo($value)->describe(VerbosityLevel::precise()),
			'createAllGreaterThan' => IntegerRangeType::createAllGreaterThan($value)->describe(VerbosityLevel::precise()),
			'createAllGreaterThanOrEqualTo' => IntegerRangeType::createAllGreaterThanOrEqualTo($value)->describe(VerbosityLevel::precise()),
		]);
	}

	public static function dataFloatBounds(): iterable
	{
		$smallerThan = static fn (float $value): Type => IntegerRangeType::createAllSmallerThan($value);
		$smallerThanOrEqualTo = static fn (float $value): Type => IntegerRangeType::createAllSmallerThanOrEqualTo($value);
		$greaterThan = static fn (float $value): Type => IntegerRangeType::createAllGreaterThan($value);
		$greaterThanOrEqualTo = static fn (float $value): Type => IntegerRangeType::createAllGreaterThanOrEqualTo($value);

		// no int compares with NaN
		yield [$smallerThan, NAN, '*NEVER*'];
		yield [$smallerThanOrEqualTo, NAN, '*NEVER*'];
		yield [$greaterThan, NAN, '*NEVER*'];
		yield [$greaterThanOrEqualTo, NAN, '*NEVER*'];

		yield [$smallerThan, INF, 'int'];
		yield [$smallerThanOrEqualTo, INF, 'int'];
		yield [$greaterThan, INF, '*NEVER*'];
		yield [$greaterThanOrEqualTo, INF, '*NEVER*'];
		yield [$smallerThan, -INF, '*NEVER*'];
		yield [$greaterThanOrEqualTo, -INF, 'int'];

		yield [$smallerThan, 2.5, 'int<min, 2>'];
		yield [$smallerThanOrEqualTo, 2.5, 'int<min, 2>'];
		yield [$greaterThan, 2.5, 'int<3, max>'];
		yield [$greaterThanOrEqualTo, 2.5, 'int<3, max>'];
		yield [$smallerThan, -2.0, 'int<min, -3>'];
		yield [$greaterThanOrEqualTo, -2.0, 'int<-2, max>'];
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

	public static function dataMatchesPhpComparison(): iterable
	{
		// floats whose int bounds depend on the int width: (float) PHP_INT_MAX is 2**63 on 64-bit
		// builds and exact on 32-bit ones, and so is PHP_INT_MAX - 0.5 on 32-bit ones
		$values = [
			'(float) PHP_INT_MAX' => (float) PHP_INT_MAX,
			'PHP_INT_MAX - 0.5' => PHP_INT_MAX - 0.5,
			'PHP_INT_MAX + 0.5' => PHP_INT_MAX + 0.5,
			'(float) PHP_INT_MIN' => (float) PHP_INT_MIN,
			'PHP_INT_MIN + 0.5' => PHP_INT_MIN + 0.5,
			'PHP_INT_MIN - 0.5' => PHP_INT_MIN - 0.5,
			'2**31' => 2147483648.0,
			'2**53 + 4' => 9007199254740996.0,
			'-2**53 - 4' => -9007199254740996.0,
		];
		$comparisons = [
			'<' => [
				static fn (float $value): Type => IntegerRangeType::createAllSmallerThan($value),
				static fn (int $int, float $value): bool => $int < $value,
			],
			'<=' => [
				static fn (float $value): Type => IntegerRangeType::createAllSmallerThanOrEqualTo($value),
				static fn (int $int, float $value): bool => $int <= $value,
			],
			'>' => [
				static fn (float $value): Type => IntegerRangeType::createAllGreaterThan($value),
				static fn (int $int, float $value): bool => $int > $value,
			],
			'>=' => [
				static fn (float $value): Type => IntegerRangeType::createAllGreaterThanOrEqualTo($value),
				static fn (int $int, float $value): bool => $int >= $value,
			],
		];
		foreach ($values as $label => $value) {
			foreach ($comparisons as $operator => [$factory, $compare]) {
				yield sprintf('$int %s %s', $operator, $label) => [$factory, $compare, $value];
			}
		}
	}

	/**
	 * @param callable(float): Type $factory
	 * @param callable(int, float): bool $compare
	 */
	#[DataProvider('dataMatchesPhpComparison')]
	public function testMatchesPhpComparison(callable $factory, callable $compare, float $value): void
	{
		$type = $factory($value);

		// a comparison with a float flips at most once, near the float or at an end of the int range;
		// the ints converting to one float near 2**63 span 1024 of them
		$centers = [PHP_INT_MIN, PHP_INT_MAX];
		if ($value > PHP_INT_MIN && $value < PHP_INT_MAX) {
			$centers[] = (int) $value;
		}
		$mismatches = [];
		foreach ($centers as $center) {
			// clamped to the int range without overflowing into float
			$from = $center < PHP_INT_MIN + self::PROBED_INTS_AROUND_BOUND ? PHP_INT_MIN : $center - self::PROBED_INTS_AROUND_BOUND;
			$to = $center > PHP_INT_MAX - self::PROBED_INTS_AROUND_BOUND ? PHP_INT_MAX : $center + self::PROBED_INTS_AROUND_BOUND;
			for ($int = $from;; $int++) {
				if ($type->isSuperTypeOf(new ConstantIntegerType($int))->yes() !== $compare($int, $value)) {
					$mismatches[] = $int;
				}
				if ($int === $to) {
					break;
				}
			}
		}

		$this->assertSame([], $mismatches, $type->describe(VerbosityLevel::precise()));
	}

}
