<?php declare(strict_types = 1);

/*
 * instructions: 213.5M
 * cycles: 266.5M
 * memory: 962.3KiB
 */

namespace BenchNestedNullableShapeAccepts;

/**
 * @param array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: int}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null $a
 * @param array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: int}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null $b
 * @return array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: int}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null
 */
function f($a, $b, bool $c)
{
	$x = $c ? $a : $b;
	g($x);
	h([$a, $b]);
	k($x);

	return $x;
}

/** @param array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: int}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null $p */
function g($p): void
{
}

/** @param array<array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: int}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null> $p */
function h(array $p): void
{
}

/** @param array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: array{a: string}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null}|null $p */
function k($p): void
{
}
