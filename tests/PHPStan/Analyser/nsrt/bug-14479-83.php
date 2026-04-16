<?php // lint >= 8.3

namespace Bug14479Php83;

use function PHPStan\Testing\assertType;

function test(string $input) {
	assertType(\DateInterval::class, \DateInterval::createFromDateString($input));
}

function test2() {
	assertType('*NEVER*', \DateInterval::createFromDateString('foo'));
}
