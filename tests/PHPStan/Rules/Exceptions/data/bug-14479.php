<?php // lint >= 8.3

namespace Bug14479;


function test(string $input) {
	try {
		\DateInterval::createFromDateString($input);
	} catch (\Exception $e) {
	}
}

function testValid() {
	try {
		\DateInterval::createFromDateString('P1D');
	} catch (\Exception $e) {
	}
}

function testInvalid() {
	try {
		\DateInterval::createFromDateString('foo');
	} catch (\Exception $e) {
	}
}

/** @param 'P1D'|'foo' $input */
function testUnion(string $input) {
	try {
		\DateInterval::createFromDateString($input);
	} catch (\Exception $e) {
	}
}
