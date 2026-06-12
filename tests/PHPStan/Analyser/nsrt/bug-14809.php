<?php declare(strict_types = 1);

namespace Bug14809;

use function PHPStan\Testing\assertType;

function doFoo(string $s): void
{
	// the outer "(" / ")" are the regex delimiters, the actual pattern is ".?(.|$)"
	// the "(.|$)" branch may match the empty string at "$", so the whole match may be empty
	if (preg_match('(.?(.|$))', $s, $m)) {
		assertType('array{string, string}', $m);
	}

	if (preg_match('((.?(.|$)))', $s, $m)) {
		assertType('array{string, string, string}', $m);
	}

	if (preg_match('((.?(.|.?)))', $s, $m)) {
		assertType('array{string, string, string}', $m);
	}

	// guard: an alternation whose branches are all non-empty stays non-falsy
	if (preg_match('~(a(b|c))~', $s, $m)) {
		assertType("array{non-falsy-string, 'ab'|'ac', 'b'|'c'}", $m);
	}
}
