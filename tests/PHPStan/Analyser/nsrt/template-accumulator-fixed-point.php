<?php declare(strict_types = 1);

namespace TemplateAccumulatorFixedPoint;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @template TCarry
 * @param list<T> $xs
 * @param callable(TCarry, T): TCarry $f
 * @param TCarry $init
 * @return TCarry
 */
function reduce(array $xs, callable $f, $init)
{
	foreach ($xs as $x) {
		$init = $f($init, $x);
	}

	return $init;
}

/**
 * @template T
 * @template TCarry
 * @param list<T> $xs
 * @param callable(TCarry, T): TCarry $f
 * @return TCarry|null
 */
function reduceWithoutInit(array $xs, callable $f)
{
	return null;
}

/**
 * @template T
 * @param list<T> $list
 * @param callable(T): T $fn
 * @return list<T>
 */
function apply(array $list, callable $fn): array
{
	return array_map($fn, $list);
}

/**
 * @template T
 * @template U
 * @param T $init
 * @param callable(T): U $f
 * @param callable(U): T $g
 * @return T
 */
function pingPong($init, callable $f, callable $g)
{
	return $init;
}

/**
 * @template T
 * @template U
 * @param callable(U): T $g
 * @param callable(T): U $f
 * @param T $init
 * @return T
 */
function pongPing(callable $g, callable $f, $init)
{
	return $init;
}

/** @param list<int> $ints */
function test(array $ints): void
{
	// the accumulator starts from $init and takes the closure's returns until it stops growing
	assertType('int', reduce($ints, fn ($carry, $x) => $carry + $x, 0));
	assertType('int', array_reduce($ints, fn ($carry, $x) => $carry + $x, 0));
	// the constants that keep growing widen as in a loop
	assertType('int<0, max>', array_reduce($ints, fn ($carry, $x) => $carry + 1, 0));
	assertType('list<int>', reduce($ints, static function ($carry, $x) {
		assertType('list<int>', $carry);
		return [...$carry, $x];
	}, []));
	assertType('lowercase-string&uppercase-string', array_reduce($ints, static function ($carry, $x) {
		assertType('lowercase-string&uppercase-string', $carry);
		return $carry . $x;
	}, ''));

	assertType("list<'a'|'b'>", apply(['a', 'b'], static function (string $s): string {
		assertType("'a'|'b'", $s);
		return $s;
	}));
	assertType('list<literal-string&lowercase-string&non-falsy-string>', apply(['a', 'b'], static fn ($s) => $s . 'x'));

	// a typed accumulator is unchanged
	assertType('int', reduce($ints, static fn (int $carry, int $x): int => $carry + $x, 0));

	// without a seed only the closure's return determines the accumulator, as before
	assertType('int|null', reduceWithoutInit($ints, static fn ($carry, $x) => $x));

	// a cycle through two closures is solved together, in the order its data flows
	assertType('int<0, max>', pingPong(0, static function (int $t) {
		assertType('int<0, max>', $t);
		return [$t];
	}, static function (array $u) {
		assertType('array{int<0, max>}', $u);
		return $u[0] + 1;
	}));
	assertType('int<0, max>', pongPing(static fn (array $u) => $u[0] + 1, static fn (int $t) => [$t], 0));
	assertType('0|2', pingPong(0, static fn ($t) => [$t], static fn ($u) => count($u) + 1));
}
