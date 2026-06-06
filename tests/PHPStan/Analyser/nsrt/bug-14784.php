<?php declare(strict_types = 1);

namespace Bug14784;

use function PHPStan\Testing\assertType;

function doFoo(string $str): void
{
	if (preg_match('/^(?:0|-?[1-9][0-9]*)$/', $str)) {
		assertType('decimal-int-string', $str);
	}

	// capturing group variant
	if (preg_match('/^(0|-?[1-9][0-9]*)$/', $str, $matches)) {
		assertType('decimal-int-string', $str);
		assertType("array{decimal-int-string, '0'|(decimal-int-string&non-falsy-string)}", $matches);
	}

	// alternation of two decimal literals
	if (preg_match('/^(?:0|123)$/', $str)) {
		assertType('decimal-int-string', $str);
	}

	// alternation without a leading sign
	if (preg_match('/^(?:0|[1-9][0-9]*)$/', $str)) {
		assertType('decimal-int-string', $str);
	}

	// alternation digits prefixed by required digits stays decimal
	if (preg_match('/^[0-9]+(?:0|5)$/', $str)) {
		assertType('decimal-int-string', $str);
	}

	// an alternation branch that is not decimal breaks the narrowing
	if (preg_match('/^(?:0|abc)$/', $str)) {
		assertType('non-empty-string', $str);
	}

	// a capturing decimal alternation keeps the group decimal too
	if (preg_match('/^(0|[1-9][0-9]*)$/', $str, $matches)) {
		assertType("'0'|(decimal-int-string&non-falsy-string)", $matches[1]);
	}
}
