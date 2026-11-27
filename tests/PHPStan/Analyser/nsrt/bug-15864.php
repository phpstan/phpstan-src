<?php declare(strict_types = 1);

namespace Bug15864;

use function PHPStan\Testing\assertType;

/** @param array{body: int}|array{title: int} $x */
function takesOne(array $x): void {}

/** @param array{'title'}|array{'body'} $keys */
function f(array $keys): void
{
	assertType("array{'body'}|array{'title'}", $keys);
	$picked = array_fill_keys($keys, 1);
	assertType('array{body: 1}|array{title: 1}', $picked);
	takesOne($picked);

	assertType("array{body: 0}|array{title: 0}", array_flip($keys));
}

/**
 * @param array{int<-32768, 32767>}|array{string} $disjoint
 * @param array{int}|array{1|string} $overlapping
 * @param array{a: 'x'}|array{a: 'y', b: 1} $differentKeys
 */
function g(array $disjoint, array $overlapping, array $differentKeys): void
{
	assertType('array{int<-32768, 32767>}|array{string}', $disjoint);
	assertType('array{int|string}', $overlapping);
	assertType("array{a: 'x'}|array{a: 'y', b: 1}", $differentKeys);
}
