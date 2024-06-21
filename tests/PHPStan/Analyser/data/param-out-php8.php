<?php

namespace ParamOutPhp8;

use function PHPStan\Testing\assertType;

function fooMatch(string $input): void {
	preg_match_all('/@[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)/', $input, $matches, PREG_PATTERN_ORDER);
	assertType('array<list<string>>', $matches);

	preg_match_all('/@[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)/', $input, $matches, PREG_SET_ORDER);
	assertType('list<array<string>>', $matches);

	preg_match('/@[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)/', $input, $matches, PREG_UNMATCHED_AS_NULL);
	assertType("array{0?: string}", $matches);
}

function testMatch() {
	preg_match('#.*#', 'foo', $matches);
	assertType('array{0?: string}', $matches);
}

