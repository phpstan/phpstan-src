<?php declare(strict_types = 1);

namespace Bug2572Nsrt;

use function PHPStan\Testing\assertType;

/**
 * @template TE
 * @template TR
 *
 * @param TE $elt
 * @param TR ...$elts
 *
 * @return TE|TR
 */
function collect($elt, ...$elts) {
	$ret = $elt;
	foreach ($elts as $item) {
		if (rand(0, 1)) {
			$ret = $item;
		}
	}
	return $ret;
}

assertType("'a'", collect("a"));
assertType("'a'|'b'|'c'", collect("a", "b", "c"));

/**
 * @template TValue
 * @template TArgs
 *
 * @param  TValue|\Closure(TArgs): TValue  $value
 * @param  TArgs  ...$args
 * @return TValue
 */
function value($value, ...$args)
{
	return $value instanceof \Closure ? $value(...$args) : $value;
}

assertType("'foo'", value('foo'));
assertType("'foo'", value('foo', 42));
assertType('42', value(fn () => 42));
assertType('42', value(function ($foo) {
	assertType('true', $foo);

	return 42;
}, true));
