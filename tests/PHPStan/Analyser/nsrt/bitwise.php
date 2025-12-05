<?php

namespace Bitwise;

use function PHPStan\Testing\assertType;

/**
 * @param string|int $stringOrInt
 * @param mixed $mixed
 */
function test(int $int, string $string, $stringOrInt, $mixed) : void
{
	assertType('int', $int & $int);
	assertType('*ERROR*', $int & $string);
	assertType('*ERROR*', $int & $stringOrInt);
	assertType('int', $int & $mixed);
	assertType('*ERROR*', $string & $int);
	assertType('string', $string & $string);
	assertType('*ERROR*', $string & $stringOrInt);
	assertType('string', $string & $mixed);
	assertType('*ERROR*', $stringOrInt & $int);
	assertType('*ERROR*', $stringOrInt & $string);
	assertType('*ERROR*', $stringOrInt & $stringOrInt);
	assertType('*ERROR*', $stringOrInt & $mixed);
	assertType('int', $mixed & $int);
	assertType('string', $mixed & $string);
	assertType('*ERROR*', $mixed & $stringOrInt);
	assertType('(int|string)', $mixed & $mixed);

	assertType('int', $int | $int);
	assertType('*ERROR*', $int | $string);
	assertType('*ERROR*', $int | $stringOrInt);
	assertType('int', $int | $mixed);
	assertType('*ERROR*', $string | $int);
	assertType('string', $string | $string);
	assertType('*ERROR*', $string | $stringOrInt);
	assertType('string', $string | $mixed);
	assertType('*ERROR*', $stringOrInt | $int);
	assertType('*ERROR*', $stringOrInt | $string);
	assertType('*ERROR*', $stringOrInt | $stringOrInt);
	assertType('*ERROR*', $stringOrInt | $mixed);
	assertType('int', $mixed | $int);
	assertType('string', $mixed | $string);
	assertType('*ERROR*', $mixed | $stringOrInt);
	assertType('(int|string)', $mixed | $mixed);

	assertType('int', $int ^ $int);
	assertType('*ERROR*', $int ^ $string);
	assertType('*ERROR*', $int ^ $stringOrInt);
	assertType('int', $int ^ $mixed);
	assertType('*ERROR*', $string ^ $int);
	assertType('string', $string ^ $string);
	assertType('*ERROR*', $string ^ $stringOrInt);
	assertType('string', $string ^ $mixed);
	assertType('*ERROR*', $stringOrInt ^ $int);
	assertType('*ERROR*', $stringOrInt ^ $string);
	assertType('*ERROR*', $stringOrInt ^ $stringOrInt);
	assertType('*ERROR*', $stringOrInt ^ $mixed);
	assertType('int', $mixed ^ $int);
	assertType('string', $mixed ^ $string);
	assertType('*ERROR*', $mixed ^ $stringOrInt);
	assertType('(int|string)', $mixed ^ $mixed);
}
