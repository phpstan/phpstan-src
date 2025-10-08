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
	assertType('string', $string & $string);
	assertType('*ERROR*', $string & $stringOrInt);
	assertType('*ERROR*', $string & $mixed);
	assertType('*ERROR*', $stringOrInt & $stringOrInt);
	assertType('*ERROR*', $stringOrInt & $mixed);
	assertType('*ERROR*', $mixed & $mixed);

	assertType('int', $int | $int);
	assertType('*ERROR*', $int | $string);
	assertType('*ERROR*', $int | $stringOrInt);
	assertType('int', $int | $mixed);
	assertType('string', $string | $string);
	assertType('*ERROR*', $string | $stringOrInt);
	assertType('*ERROR*', $string | $mixed);
	assertType('*ERROR*', $stringOrInt | $stringOrInt);
	assertType('*ERROR*', $stringOrInt | $mixed);
	assertType('*ERROR*', $mixed | $mixed);

	assertType('int', $int ^ $int);
	assertType('*ERROR*', $int ^ $string);
	assertType('*ERROR*', $int ^ $stringOrInt);
	assertType('int', $int ^ $mixed);
	assertType('string', $string ^ $string);
	assertType('*ERROR*', $string ^ $stringOrInt);
	assertType('*ERROR*', $string ^ $mixed);
	assertType('*ERROR*', $stringOrInt ^ $stringOrInt);
	assertType('*ERROR*', $stringOrInt ^ $mixed);
	assertType('*ERROR*', $mixed ^ $mixed);
}
