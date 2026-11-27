<?php declare(strict_types = 1);

namespace NestedGenericCallInClosureArgument;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @template U
 * @param list<T> $xs
 * @param callable(T): U $f
 * @return list<U>
 */
function map(array $xs, callable $f): array { return array_map($f, $xs); }

/**
 * @template T
 * @template X
 * @param T $t
 * @param X $x
 * @return array{T, X}
 */
function pair($t, $x): array { return [$t, $x]; }

/**
 * @template T
 * @template R
 * @param T $x
 * @param callable(T): R $f
 * @return R
 */
function apply($x, callable $f) { return $f($x); }

function sameTemplateName(): void
{
	assertType("list<list<'a'>>", map(['a'], fn ($a) => map([2], fn ($b) => $a)));
	assertType('list<list<array{1, 2}>>', map([1], fn ($a) => map([2], fn ($b) => [$a, $b])));
	assertType("list<array{2, 'a'}>", map(['a'], fn ($a) => pair(2, $a)));
	assertType("list<'a'>", map(['a'], fn ($a) => apply(2, fn ($b) => $a)));
}

function deeplyNested(): void
{
	assertType('list<list<list<array{1, 2, 3}>>>', map([1], fn ($a) => map([2], fn ($b) => map([3], fn ($c) => [$a, $b, $c]))));
	assertType('list<list<list<list<array{1, 2, 3, 4}>>>>', map([1], fn ($a) => map([2], fn ($b) => map([3], fn ($c) => map([4], fn ($d) => [$a, $b, $c, $d])))));
	assertType("list<list<list<list<'x'>>>>", map([1], function ($a) { return map([2], function ($b) { return map([3], function ($c) { return map([4], function ($d) { return 'x'; }); }); }); }));
}
