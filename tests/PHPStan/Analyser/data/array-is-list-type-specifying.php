<?php

namespace ArrayIsList;

use function PHPStan\Testing\assertType;

function foo(array $foo) {
	if (array_is_list($foo)) {
        assertType('list<mixed>', $foo);
    } else {
		assertType('array', $foo);
	}
}

$bar = [1, 2, 3];

if (array_is_list($bar)) {
    assertType('array{1, 2, 3}', $bar);
} else {
	assertType('*NEVER*', $bar);
}

/** @var array<int|string, mixed> $foo */

if (array_is_list($foo)) {
    assertType('list<mixed>', $foo);
} else {
	assertType('array<int|string, mixed>', $foo);
}

$baz = [];

if (array_is_list($baz)) {
    assertType('array{}', $baz);
}
