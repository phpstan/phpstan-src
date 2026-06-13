<?php declare(strict_types = 1);

namespace Bug14820;

use function PHPStan\Testing\assertType;

function doFoo(string $s): void
{
	// a single optional literal yields the literal or the empty string,
	// combined with the surrounding literals
	if (preg_match('~(ab?)~', $s, $m)) {
		assertType("array{non-falsy-string, 'a'|'ab'}", $m);
	}

	if (preg_match('~(ab?c)~', $s, $m)) {
		assertType("array{non-falsy-string, 'abc'|'ac'}", $m);
	}

	// optional in front
	if (preg_match('~(a?bc)~', $s, $m)) {
		assertType("array{non-falsy-string, 'abc'|'bc'}", $m);
	}

	// two optionals combine into the full cross-product
	if (preg_match('~(a?b?)~', $s, $m)) {
		assertType("array{string, ''|'a'|'ab'|'b'}", $m);
	}

	// optional over a (sub) group of literals
	if (preg_match('~(a(bc)?d)~', $s, $m)) {
		assertType("array{0: non-falsy-string, 1: 'abcd'|'ad', 2?: 'bc'}", $m);
	}

	// optional over an alternation
	if (preg_match('~(a(b|c)?d)~', $s, $m)) {
		assertType("array{0: non-falsy-string, 1: 'abd'|'acd'|'ad', 2?: 'b'|'c'}", $m);
	}

	// the classic colour/color example
	if (preg_match('~(colou?r)~', $s, $m)) {
		assertType("array{non-falsy-string, 'color'|'colour'}", $m);
	}

	// exactly-n repetition of a literal
	if (preg_match('~(ab{2}c)~', $s, $m)) {
		assertType("array{non-falsy-string, 'abbc'}", $m);
	}

	// n-to-m repetition of a literal
	if (preg_match('~(ab{1,2}c)~', $s, $m)) {
		assertType("array{non-falsy-string, 'abbc'|'abc'}", $m);
	}

	// unbounded repetition stays non-constant
	if (preg_match('~(ab*c)~', $s, $m)) {
		assertType('array{non-falsy-string, non-falsy-string}', $m);
	}
}
