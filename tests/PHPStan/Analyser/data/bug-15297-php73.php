<?php declare(strict_types = 1);

namespace Bug15297Php73;

use function PHPStan\Testing\assertType;

$pattern = "/^(?:
	(?P<foo1>foo[a-z]+)
	(?:-(?P<bar1>[0-9]+))?
	|
	(?P<foo2>foo[0-9]+)
	(?:-(?P<bar2>[a-z]+))?
)$/ix";
if (preg_match($pattern, "foobar", $matches, \PREG_UNMATCHED_AS_NULL)) {
	assertType("array{0: non-falsy-string, foo1: non-falsy-string, 1: non-falsy-string, bar1?: numeric-string, 2?: numeric-string}|array{0: non-falsy-string, foo1: null, 1: null, bar1: null, 2: null, foo2: non-falsy-string, 3: non-falsy-string, bar2?: non-empty-string, 4?: non-empty-string}", $matches);
	$foo = $matches["foo1"] ?? $matches["foo2"] ?? "";
	$bar = $matches["bar1"] ?? $matches["bar2"] ?? "";
}

function (string $s): void {
	if (preg_match('/(a)?(b)?(c)/', $s, $matches, \PREG_UNMATCHED_AS_NULL)) {
		assertType("array{non-falsy-string, 'a'|null, 'b'|null, 'c'}", $matches);
	}
	if (preg_match('/(a)(b)?(c)?/', $s, $matches, \PREG_UNMATCHED_AS_NULL)) {
		assertType("list{0: non-falsy-string, 1: 'a', 2?: 'b'|null, 3?: 'c'}", $matches);
	}
};
