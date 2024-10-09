<?php // lint >= 8.0

declare(strict_types=1);

namespace RoundFamilyTestPHP8StrictTypes;

use function PHPStan\Testing\assertType;

$maybeNull = null;
if (rand(0, 1)) {
	$maybeNull = 1.0;
}

// Round
assertType('123.0', round(123));
assertType('123.0', round(123.456));
assertType('float', round($_GET['foo'] / 60));
assertType('*NEVER*', round('123'));
assertType('*NEVER*', round('123.456'));
assertType('*NEVER*', round(null));
assertType('float', round($maybeNull));
assertType('*NEVER*', round(true));
assertType('*NEVER*', round(false));
assertType('*NEVER*', round(new \stdClass));
assertType('*NEVER*', round(''));
assertType('*NEVER*', round(array()));
assertType('*NEVER*', round(array(123)));
assertType('*NEVER*', round());
assertType('float', round($_GET['foo']));

// Ceil
assertType('123.0', ceil(123));
assertType('124.0', ceil(123.456));
assertType('float', ceil($_GET['foo'] / 60));
assertType('*NEVER*', ceil('123'));
assertType('*NEVER*', ceil('123.456'));
assertType('*NEVER*', ceil(null));
assertType('float', ceil($maybeNull));
assertType('*NEVER*', ceil(true));
assertType('*NEVER*', ceil(false));
assertType('*NEVER*', ceil(new \stdClass));
assertType('*NEVER*', ceil(''));
assertType('*NEVER*', ceil(array()));
assertType('*NEVER*', ceil(array(123)));
assertType('*NEVER*', ceil());
assertType('float', ceil($_GET['foo']));

// Floor
assertType('123.0', floor(123));
assertType('123.0', floor(123.456));
assertType('float', floor($_GET['foo'] / 60));
assertType('*NEVER*', floor('123'));
assertType('*NEVER*', floor('123.456'));
assertType('*NEVER*', floor(null));
assertType('float', floor($maybeNull));
assertType('*NEVER*', floor(true));
assertType('*NEVER*', floor(false));
assertType('*NEVER*', floor(new \stdClass));
assertType('*NEVER*', floor(''));
assertType('*NEVER*', floor(array()));
assertType('*NEVER*', floor(array(123)));
assertType('*NEVER*', floor());
assertType('float', floor($_GET['foo']));

/**
 * @param 1.11|2.22 $floatUnionA
 * @param 1.5|2.5 $floatUnionB
 * @param 1.1|2.2|5.5|6.6 $floatUnionC
 */
function constant(float $floatUnionA, float $floatUnionB, float $floatUnionC)
{
	assertType('3.0', round(3.4));
	assertType('4.0', round(3.5));
	assertType('4.0', round(3.6));
	assertType('4.0', round(3.6, 0));
	assertType('5.05', round(5.045, 2));
	assertType('5.06', round(5.055, 2));
	assertType('300.0', round(345, -2));
	assertType('0.0', round(345, -3));
	assertType('700.0', round(678, -2));
	assertType('1000.0', round(678, -3));

	assertType('1.1|2.2', round($floatUnionA, 1));
	assertType('1.1|2.2', round($floatUnionA, 1, PHP_ROUND_HALF_UP));
	assertType('1.0|2.0', round($floatUnionA, mode: PHP_ROUND_HALF_UP));
	assertType('2.0|3.0', round($floatUnionB, mode: PHP_ROUND_HALF_UP));
	assertType('1.0|2.0', round($floatUnionB, mode: PHP_ROUND_HALF_DOWN));
	assertType('1.0|2.0|5.0|6.0', floor($floatUnionC));

	$number = 135.79;
	assertType('135.79', round($number, 3));
	assertType('135.79', round($number, 2));
	assertType('135.8', round($number, 1));
	assertType('136.0', round($number, 0));
	assertType('140.0', round($number, -1));
	assertType('100.0', round($number, -2));
	assertType('0.0', round($number, -3));

	// Rounding modes with 9.5
	assertType('10.0', round(9.5, 0, PHP_ROUND_HALF_UP));
	assertType('9.0', round(9.5, 0, PHP_ROUND_HALF_DOWN));
	assertType('10.0', round(9.5, 0, PHP_ROUND_HALF_EVEN));
	assertType('9.0', round(9.5, 0, PHP_ROUND_HALF_ODD));

	// Rounding modes with 8.5
	assertType('9.0', round(8.5, 0, PHP_ROUND_HALF_UP));
	assertType('8.0', round(8.5, 0, PHP_ROUND_HALF_DOWN));
	assertType('8.0', round(8.5, 0, PHP_ROUND_HALF_EVEN));
	assertType('9.0', round(8.5, 0, PHP_ROUND_HALF_ODD));

	// Using PHP_ROUND_HALF_UP with 1 decimal digit precision
	assertType('1.6', round( 1.55, 1, PHP_ROUND_HALF_UP));
	assertType('-1.6', round(-1.55, 1, PHP_ROUND_HALF_UP));

	// Using PHP_ROUND_HALF_DOWN with 1 decimal digit precision
	assertType('1.5', round( 1.55, 1, PHP_ROUND_HALF_DOWN));
	assertType('-1.5', round(-1.55, 1, PHP_ROUND_HALF_DOWN));

	// Using PHP_ROUND_HALF_EVEN with 1 decimal digit precision
	assertType('1.6', round( 1.55, 1, PHP_ROUND_HALF_EVEN));
	assertType('-1.6', round(-1.55, 1, PHP_ROUND_HALF_EVEN));

	// Using PHP_ROUND_HALF_ODD with 1 decimal digit precision
	assertType('1.5', round( 1.55, 1, PHP_ROUND_HALF_ODD));
	assertType('-1.5', round(-1.55, 1, PHP_ROUND_HALF_ODD));
}

/**
 * @param 1.11|2.22 $floatUnion
 * @param 2|3 $precisionUnion
 * @param 2|4 $modeUnion
 * @param 1|'2.5' $IntOrNumStr
 * @param 1.11|'2.22' $floatOrNumStr
 */
function notConstant(float $floatUnion, float $precisionUnion, float $modeUnion, $IntOrNumStr, $floatOrNumStr)
{
	assertType('float', round($floatUnion, $precisionUnion, PHP_ROUND_HALF_UP));
	assertType('float', round($floatUnion, 0, $modeUnion));

	assertType('float', round($IntOrNumStr));
	assertType('float', round($IntOrNumStr, mode: PHP_ROUND_HALF_UP));
	assertType('float', round($IntOrNumStr, mode: PHP_ROUND_HALF_DOWN));

	assertType('float', round($floatOrNumStr));
	assertType('float', round($floatOrNumStr, mode: PHP_ROUND_HALF_UP));
	assertType('float', round($floatOrNumStr, mode: PHP_ROUND_HALF_DOWN));
}
