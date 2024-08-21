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

function foo2($foo) {
	if (array_is_list($foo)) {
		assertType('list<mixed>', $foo);
	} else {
		assertType('mixed~list<mixed>', $foo);
	}
}

/** @param array{'foo', 'bar'}|array{'baz', 'foobar'} $foo */
function foo3(array $foo) {
	if (array_is_list($foo)) {
		assertType("array{'baz', 'foobar'}|array{'foo', 'bar'}", $foo);
	} else {
		assertType('*NEVER*', $foo);
	}
}

/** @param array{foo: 0, bar: 1}|array{baz: 3, foobar: 4} $foo */
function foo4(array $foo) {
	if (array_is_list($foo)) {
		assertType('*NEVER*', $foo);
	} else {
		assertType('array{baz: 3, foobar: 4}|array{foo: 0, bar: 1}', $foo);
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
