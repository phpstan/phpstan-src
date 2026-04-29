<?php // lint < 8.3

namespace Bug14479Php82;

use function PHPStan\Testing\assertType;

function test(string $input) {
	assertType('DateInterval|false', \DateInterval::createFromDateString($input));
}

function testValid() {
	assertType('DateInterval', \DateInterval::createFromDateString('P1D'));
}

function testInvalid() {
	assertType('false', \DateInterval::createFromDateString('foo'));
}

/** @param 'P1D'|'foo' $input */
function testUnion(string $input) {
	assertType('DateInterval|false', \DateInterval::createFromDateString($input));
}
