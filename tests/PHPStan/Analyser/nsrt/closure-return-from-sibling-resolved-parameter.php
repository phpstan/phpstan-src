<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClosureReturnFromSiblingResolvedParameter;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @template U
 * @param list<T> $xs
 * @param callable(T): U $f
 * @return list<U>
 */
function map(array $xs, callable $f): array
{
	return array_map($f, $xs);
}

/**
 * @template T
 * @template U
 * @template V
 * @param T $x
 * @param callable(T): U $f
 * @param callable(U): V $g
 * @return V
 */
function pipe2($x, callable $f, callable $g)
{
	return $g($f($x));
}

/**
 * @template T
 * @param list<int> $xs
 * @param callable(T, int): T $f
 * @param T $init
 * @return T
 */
function reduce(array $xs, callable $f, $init)
{
	foreach ($xs as $x) {
		$init = $f($init, $x);
	}

	return $init;
}

final class Collection
{

	/**
	 * @template T
	 * @template U
	 * @param list<T> $xs
	 * @param callable(T): U $f
	 * @return list<U>
	 */
	public function map(array $xs, callable $f): array
	{
		return array_map($f, $xs);
	}

}

function test(Collection $c): void
{
	assertType('list<2|3>', map([1, 2], fn ($x) => $x + 1));
	assertType("list<'a'>", map([1, 2], fn ($x) => is_int($x) ? 'a' : null));
	assertType('list<2|3>', map(f: fn ($x) => $x + 1, xs: [1, 2]));
	assertType('list<2|3>', $c->map([1, 2], fn ($x) => $x + 1));
	assertType('array{1}', pipe2(1, fn ($x) => [$x], fn ($y) => $y));

	// the closure's own return feeds T, so T is not taken from $init alone:
	// the closure is typed with '' first, and its returns join T until T stops growing
	assertType("''|(literal-string&non-falsy-string)", reduce([1, 2, 3], fn ($carry, $x) => $carry . 'a', ''));
}
