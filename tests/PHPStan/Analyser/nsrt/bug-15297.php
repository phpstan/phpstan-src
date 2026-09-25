<?php declare(strict_types = 1);

namespace Bug15297;

use function PHPStan\Testing\assertType;

function doFoo(string $s): void
{
	$pattern = "/^(?:
	(?P<foo1>foo[a-z]+)
	(?:-(?P<bar1>[0-9]+))?
	|
	(?P<foo2>foo[0-9]+)
	(?:-(?P<bar2>[a-z]+))?
)$/ix";
	if (preg_match($pattern, $s, $matches)) {
		assertType("array{0: non-falsy-string, foo1: '', 1: '', bar1: '', 2: '', foo2: non-falsy-string, 3: non-falsy-string, bar2?: non-empty-string, 4?: non-empty-string}|array{0: non-falsy-string, foo1: non-falsy-string, 1: non-falsy-string, bar1?: numeric-string, 2?: numeric-string}", $matches);
	}
}

function doBar(string $s): void
{
	if (preg_match('/^(?:(?:(a))?(b)|(c))$/', $s, $matches)) {
		assertType("array{non-falsy-string, '', '', 'c'}|list{0: non-falsy-string, 1?: ''|'a', 2: 'b'}", $matches);
	}
}
