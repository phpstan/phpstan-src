<?php

namespace Bug10122;

use function PHPStan\Testing\assertType;

/**
 * @param string $s
 * @param numeric-string $ns
 */
function doFoo(string $s, $ns, float $f) {
	assertType('string', ++$s);
	assertType('string', --$s);
	assertType('(float|int)', ++$ns);
	assertType('(float|int)', --$ns);
	assertType('float', ++$f);
	assertType('float', --$f);
}
