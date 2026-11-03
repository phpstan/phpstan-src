<?php

namespace PowerAccessoryString;

use function PHPStan\Testing\assertType;

/**
 * @param numeric-string $numericString
 * @param non-empty-string $nonEmptyString
 * @param decimal-int-string $decimalIntString
 */
function doFoo(string $numericString, string $nonEmptyString, string $decimalIntString): void
{
	// a non-numeric string exponent is a TypeError whatever the base is
	assertType('*ERROR*', $numericString ** '');
	assertType('*ERROR*', $numericString ** 'abc');
	assertType('*ERROR*', $nonEmptyString ** 'abc');
	assertType('*ERROR*', $decimalIntString ** 'abc');

	// a numeric exponent stays valid
	assertType('(float|int)', $numericString ** '2');
	assertType('(float|int)', $decimalIntString ** 2);
}
