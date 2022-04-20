<?php

namespace WeirdStrlenCases;

use function PHPStan\Testing\assertType;
use function strlen;

class Foo
{
	/**
	 * @param 1|2|5|10|123|'1234'|false $constUnionMixed
	 * @param int|float $intFloat
	 * @param non-empty-string|int|float $nonEmptyStringIntFloat
	 * @param ""|false|null $emptyStringFalseNull
	 * @param ""|bool|null $emptyStringBoolNull
	 */
	public function strlenTests($constUnionMixed, float $float, $intFloat, $nonEmptyStringIntFloat, $emptyStringFalseNull, $emptyStringBoolNull): void
	{
		assertType('int<0, 4>', strlen($constUnionMixed));
		assertType('3', strlen(123));
		assertType('1', strlen(true));
		assertType('0', strlen(false));
		assertType('0', strlen(null));
		assertType('1', strlen(1.0));
		assertType('4', strlen(1.23));
		assertType('int<1, max>', strlen($float));
		assertType('int<1, max>', strlen($intFloat));
		assertType('int<1, max>', strlen($nonEmptyStringIntFloat));
		assertType('0', strlen($emptyStringFalseNull));
		assertType('int<0, 1>', strlen($emptyStringBoolNull));
	}
}
