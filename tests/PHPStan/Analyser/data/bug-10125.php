<?php

namespace Bug10125;

use function PHPStan\Testing\assertType;

/**
 * @param '123'|'non-numeric' $param
 */
function test(string $param): void
{
	assertType('*ERROR*', 'str' ** 10);
	assertType('*ERROR*', 10 ** 'str');
	assertType('*ERROR*', $param ** 10);
	assertType('*ERROR*', 10 ** $param);
	assertType('253295162119140625', '55' ** 10);
	assertType('1.0E+55', 10 ** '55');
}
