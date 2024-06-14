<?php

namespace Bug6624;

use function PHPStan\Testing\assertType;

/**
 * @param non-empty-string $foo
 * @param string $bar
 */
function bug6624_should_error($foo, $bar) {
	assertType('*ERROR*',$foo + 10);
	assertType('*ERROR*',$foo - 10);
	assertType('*ERROR*',$foo * 10);
	assertType('*ERROR*',$foo / 10);

	assertType('*ERROR*',10 + $foo);
	assertType('*ERROR*',10 - $foo);
	assertType('*ERROR*',10 * $foo);
	assertType('*ERROR*',10 / $foo);

	assertType('*ERROR*',$bar + 10);
	assertType('*ERROR*',$bar - 10);
	assertType('*ERROR*',$bar * 10);
	assertType('*ERROR*',$bar / 10);

	assertType('*ERROR*',10 + $bar);
	assertType('*ERROR*',10 - $bar);
	assertType('*ERROR*',10 * $bar);
	assertType('*ERROR*',10 / $bar);
}
