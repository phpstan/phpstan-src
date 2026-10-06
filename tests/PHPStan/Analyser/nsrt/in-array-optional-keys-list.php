<?php

declare(strict_types = 1);

namespace InArrayOptionalKeysList;

use function PHPStan\Testing\assertType;

/**
 * @param list{0: 'x', 1?: 'a'|'b', 2?: 'a'|'b'} $l
 */
function narrow(array $l): void
{
	if (!in_array('b', $l, true)) {
		assertType("list{0: 'x', 1?: 'a', 2?: 'a'}", $l);
	} else {
		assertType("list{0: 'x', 1?: 'a'|'b', 2?: 'a'|'b'}", $l);
	}

	if (in_array('a', $l, true)) {
		assertType("list{0: 'x', 1?: 'a'|'b', 2?: 'a'|'b'}", $l);
	}
}

/**
 * @param list{0: 'x', 1?: 'a'|'b', 2?: 'a'|'b'}&array<mixed, 'a'|'x'> $a
 * @param array{0: 'x', 1?: 'a'|'b', 2?: 'a'|'b'}&array<mixed, 'a'|'x'> $b
 */
function intersect(array $a, array $b): void
{
	assertType("list{0: 'x', 1?: 'a', 2?: 'a'}", $a);
	assertType("array{0: 'x', 1?: 'a', 2?: 'a'}", $b);
}

function guardedAppendsInLoops(bool $c): void
{
	$l = ['x'];
	foreach ([1, 2] as $_) {
		if ($c && !in_array('a', $l, true)) {
			$l[] = 'a';
		}
	}
	foreach ([1, 2] as $_) {
		if (!in_array('b', $l, true)) {
			$l[] = 'b';
		}
	}
	assertType("list{0: 'x', 1: 'a'|'b', 2?: 'a'|'b', 3?: 'b', 4?: 'b'}", $l);
}
