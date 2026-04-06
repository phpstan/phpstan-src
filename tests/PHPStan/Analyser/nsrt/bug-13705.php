<?php declare(strict_types = 1);

namespace Bug13705;

use function PHPStan\Testing\assertType;

function whileLoop(): void
{
	$quantity = random_int(1, 42);
	$codes = [];
	while (count($codes) < $quantity) {
		assertType('list<non-empty-string>', $codes);
		$code = random_bytes(16);
		if (!in_array($code, $codes, true)) {
			$codes[] = $code;
		}
	}
}

/**
 * @param list<string> $arr
 * @param int<2, 5> $boundedRange
 * @param int<2, max> $unboundedMaxRange
 * @param int<min, 5> $unboundedMinRange
 */
function countLessThanRange(array $arr, int $boundedRange, int $unboundedMaxRange, int $unboundedMinRange): void
{
	// count($arr) < $range → inverted to NOT($range <= count($arr))
	// Inner: orEqual=true, false context → falsey + max !== null + orEqual (branch 1)
	// Else: orEqual=true, true context → truthy + min !== null + orEqual (branch 3)
	if (count($arr) < $boundedRange) {
		assertType('list<string>', $arr);
	} else {
		assertType('non-empty-list<string>&hasOffsetValue(1, string)', $arr);
	}

	// count($arr) < unbounded max range → falsey + max is null → fallback via min (branch 3/4)
	if (count($arr) < $unboundedMaxRange) {
		assertType('list<string>', $arr);
	} else {
		assertType('non-empty-list<string>&hasOffsetValue(1, string)', $arr);
	}

	// count($arr) < unbounded min range → fallback branch (min is null)
	if (count($arr) < $unboundedMinRange) {
		assertType('list<string>', $arr);
	} else {
		assertType('list<string>', $arr);
	}
}

/**
 * @param list<string> $arr
 * @param int<2, 5> $boundedRange
 */
function countLessThanOrEqualRange(array $arr, int $boundedRange): void
{
	// count($arr) <= $range → inverted to NOT($range < count($arr))
	// Inner: orEqual=false, false context → falsey + max !== null + !orEqual (branch 2)
	// Else: orEqual=false, true context → truthy + min !== null + !orEqual (branch 4)
	if (count($arr) <= $boundedRange) {
		assertType('list<string>', $arr);
	} else {
		assertType('non-empty-list<string>&hasOffsetValue(1, string)&hasOffsetValue(2, string)', $arr);
	}
}

/**
 * @param list<string> $arr
 * @param int<2, 5> $boundedRange
 */
function rangeGreaterThanOrEqualCount(array $arr, int $boundedRange): void
{
	// $range >= count($arr) → same as count($arr) <= $range
	if ($boundedRange >= count($arr)) {
		assertType('list<string>', $arr);
	} else {
		assertType('non-empty-list<string>&hasOffsetValue(1, string)&hasOffsetValue(2, string)', $arr);
	}
}

/**
 * @param list<string> $arr
 * @param int<2, 5> $boundedRange
 */
function rangeLessThanOrEqualCount(array $arr, int $boundedRange): void
{
	// $range <= count($arr) → direct, orEqual=true
	// True context: truthy + orEqual + min !== null (branch 3)
	// False context: falsey + orEqual + max !== null (branch 1)
	if ($boundedRange <= count($arr)) {
		assertType('non-empty-list<string>&hasOffsetValue(1, string)', $arr);
	} else {
		assertType('list<string>', $arr);
	}
}

/**
 * @param list<string> $arr
 * @param int<2, 5> $boundedRange
 */
function rangeLessThanCount(array $arr, int $boundedRange): void
{
	// $range < count($arr) → direct, orEqual=false
	// True context: truthy + !orEqual + min !== null (branch 4)
	// False context: falsey + !orEqual + max !== null (branch 2)
	if ($boundedRange < count($arr)) {
		assertType('non-empty-list<string>&hasOffsetValue(1, string)&hasOffsetValue(2, string)', $arr);
	} else {
		assertType('list<string>', $arr);
	}
}

function whileLoopOriginal(int $length, int $quantity): void
{
	if ($length < 8) {
		throw new \InvalidArgumentException();
	}
	$codes = [];
	while ($quantity >= 1 && count($codes) < $quantity) {
		$code = '';
		for ($i = 0; $i < $length; $i++) {
			$code .= 'x';
		}
		if (!in_array($code, $codes, true)) {
			$codes[] = $code;
		}
	}
}

class HelloWorld
{
	private const MIN_LENGTH = 8;

	/**
	 * @return list<non-empty-string>
	 */
	public function generatePlainRecoveryCodes(int $length = 8, int $quantity = 8): array
	{
		if ($length < self::MIN_LENGTH) {
			throw new \InvalidArgumentException(
				$length . ' is not allowed as length for recovery codes. Must be at least ' . self::MIN_LENGTH,
				1613666803
			);
		}
		$codes = [];
		while ($quantity >= 1 && count($codes) < $quantity) {
			$code = '';
			for ($i = 0; $i < $length; $i++) {
				$code .= 'x';
			}
			if (!in_array($code, $codes, true)) {
				$codes[] = $code;
			}
		}
		return $codes;
	}
}
