<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug12038;

use function PHPStan\Testing\assertType;

/**
 * @template X
 * @template Y
 * @template Z
 *
 * @param callable(X, Y): Z $fn
 * @return callable(Y, X): Z
 */
function flip(callable $fn): callable
{
	return fn ($y, $x) => $fn($x, $y);
}

/**
 * @template A
 * @template B
 *
 * @param list<A> $fa
 * @param list<B> $fb
 * @return list<array{A, B}>
 */
function zip(array $fa, array $fb): array
{
	$length = min(count($fa), count($fb));
	$zipped = [];

	for ($i = 0; $i < $length; $i++) {
		$zipped[] = [$fa[$i], $fb[$i]];
	}

	return $zipped;
}

/**
 * @template A
 * @template B
 * @template C
 *
 * @param callable(A): B $ab
 * @param callable(B): C $bc
 * @return callable(A): C
 */
function compose(callable $ab, callable $bc): callable
{
	return fn($a) => $bc($ab($a));
}

/**
 * @template T
 * @param T $a
 * @return list<T>
 */
function toList(mixed $a): array
{
	return [$a];
}

/**
 * @template V
 * @param V $a
 * @return array{boxed: V}
 */
function box(mixed $a): array
{
	return ['boxed' => $a];
}

// flip(zip(...)) should preserve template types
$flipZip = flip(zip(...));
assertType('callable(list<B>, list<A>): list<array{A, B}>', $flipZip);

/** @var list<string> */
$strings = [];
/** @var list<int> */
$ints = [];

assertType('list<array{int, string}>', $flipZip($strings, $ints));
assertType('list<array{string, int}>', $flipZip($ints, $strings));

// compose(toList(...), box(...)) should properly unify template types
$composed1 = compose(toList(...), box(...));
assertType('callable(A): array{boxed: list<A>}', $composed1);

// compose(box(...), toList(...)) should properly unify template types
$composed2 = compose(box(...), toList(...));
assertType('callable(A): list<array{boxed: A}>', $composed2);
