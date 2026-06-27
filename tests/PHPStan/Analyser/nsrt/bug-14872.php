<?php declare(strict_types = 1);

namespace Bug14872Nsrt;

use function PHPStan\Testing\assertType;

/**
 * @template T
 */
class Column
{
}

/**
 * @extends Column<int>
 */
class IntColumn extends Column
{
}

/**
 * @template T
 * @param Column<T> $column
 * @param T $value
 * @return T
 */
function where($column, $value)
{
}

function test(IntColumn $c): void
{
	// T is anchored to int by the invariant Column<int>, not widened to int|'x'.
	assertType('int', where($c, 'x'));
	assertType('int', where($c, 5));
}

/**
 * @template T
 * @param iterable<T> $a
 * @param T $b
 * @return T
 */
function covariantPosition($a, $b)
{
}

function testCovariant(): void
{
	// iterable<T> value position is covariant, so T is still widened.
	assertType("1|2|'x'", covariantPosition([1, 2], 'x'));
}
