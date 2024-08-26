<?php declare(strict_types = 1);

// phpcs:disable SlevomatCodingStandard.ControlStructures.DisallowYodaComparison.DisallowedYodaComparison
// phpcs:disable SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
// phpcs:disable SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator
// phpcs:disable Squiz.Functions.GlobalFunction.Found
// phpcs:disable Squiz.Strings.DoubleQuoteUsage.NotRequired

namespace EqualTypeNarrowing;

use function PHPStan\Testing\assertType;

/**
 * @param 0|0.0|1|''|'0'|'x'|array{}|bool|object|null $x
 * @param int|string|null $y
 * @param mixed $z
 */
function doNull($x, $y, $z): void
{
	if ($x == null) {
		assertType("0|0.0|''|array{}|false|null", $x);
	} else {
		assertType("1|'0'|'x'|object|true", $x);
	}
	if (null != $x) {
		assertType("1|'0'|'x'|object|true", $x);
	} else {
		assertType("0|0.0|''|array{}|false|null", $x);
	}

	if ($y == null) {
		assertType("0|''|null", $y);
	} else {
		assertType("int<min, -1>|int<1, max>|non-empty-string", $y);
	}

	if ($z == null) {
		assertType("0|0.0|''|array{}|false|null", $z);
	} else {
		assertType("mixed~0|0.0|''|array{}|false|null", $z);
	}
}

/**
 * @param 0|0.0|1|''|'0'|'x'|array{}|bool|object|null $x
 * @param int|string|null $y
 * @param mixed $z
 */
function doFalse($x, $y, $z): void
{
	if ($x == false) {
		assertType("0|0.0|''|'0'|array{}|false|null", $x);
	} else {
		assertType("1|'x'|object|true", $x);
	}
	if (false != $x) {
		assertType("1|'x'|object|true", $x);
	} else {
		assertType("0|0.0|''|'0'|array{}|false|null", $x);
	}

	if (!$x) {
		assertType("0|0.0|''|'0'|array{}|false|null", $x);
	} else {
		assertType("1|'x'|object|true", $x);
	}

	if ($y == false) {
		assertType("0|''|'0'|null", $y);
	} else {
		assertType("int<min, -1>|int<1, max>|non-falsy-string", $y);
	}

	if ($z == false) {
		assertType("0|0.0|''|'0'|array{}|false|null", $z);
	} else {
		assertType("mixed~0|0.0|''|'0'|array{}|false|null", $z);
	}
}

/**
 * @param 0|0.0|1|''|'0'|'x'|array{}|bool|object|null $x
 * @param int|string|null $y
 * @param mixed $z
 */
function doTrue($x, $y, $z): void
{
	if ($x == true) {
		assertType("1|'x'|object|true", $x);
	} else {
		assertType("0|0.0|''|'0'|array{}|false|null", $x);
	}
	if (true != $x) {
		assertType("0|0.0|''|'0'|array{}|false|null", $x);
	} else {
		assertType("1|'x'|object|true", $x);
	}

	if ($x) {
		assertType("1|'x'|object|true", $x);
	} else {
		assertType("0|0.0|''|'0'|array{}|false|null", $x);
	}

	if ($y == true) {
		assertType("int<min, -1>|int<1, max>|non-falsy-string", $y);
	} else {
		assertType("0|''|'0'|null", $y);
	}

	if ($z == true) {
		assertType("mixed~0|0.0|''|'0'|array{}|false|null", $z);
	} else {
		assertType("0|0.0|''|'0'|array{}|false|null", $z);
	}
}

/**
 * @param 0|0.0|1|''|'0'|'x'|array{}|bool|object|null $x
 * @param int|string|null $y
 * @param mixed $z
 */
function doEmptyString($x, $y, $z): void
{
	// PHP 7.x/8.x compatibility: Keep zero in both cases
	if ($x == '') {
		assertType("0|0.0|''|false|null", $x);
	} else {
		assertType("0|0.0|1|'0'|'x'|array{}|object|true", $x);
	}
	if ('' != $x) {
		assertType("0|0.0|1|'0'|'x'|array{}|object|true", $x);
	} else {
		assertType("0|0.0|''|false|null", $x);
	}

	if ($y == '') {
		assertType("0|''|null", $y);
	} else {
		assertType("int|non-empty-string", $y);
	}

	if ($z == '') {
		assertType("0|0.0|''|false|null", $z);
	} else {
		assertType("mixed~''|false|null", $z);
	}
}
