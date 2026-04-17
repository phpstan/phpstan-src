<?php // lint >= 8.3

namespace Bug14479Php83;

use function PHPStan\Testing\assertType;

function test(string $input) {
	assertType('DateInterval', \DateInterval::createFromDateString($input));
}

function testValid() {
	assertType('DateInterval', \DateInterval::createFromDateString('P1D'));
}

function testInvalid() {
	assertType('*NEVER*', \DateInterval::createFromDateString('foo'));
}

/** @param 'P1D'|'foo' $input */
function testUnion(string $input) {
	assertType('DateInterval', \DateInterval::createFromDateString($input));
}
