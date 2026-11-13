<?php declare(strict_types = 1);

namespace Bug15722;

use function PHPStan\Testing\assertType;

/** @param array{r: array{a: array{b: int}|null}} $p */
function nested(array $p): bool
{
	$x = $p['r']['a']['b'] ?? null;
	assertType('array{r: array{a: array{b: int}|null}}', $p);
	assertType('int|null', $x);

	return isset($p['r']['a']);
}

/** @param array{r: array{a: array{b: int}|null}} $p */
function ternary(array $p): ?int
{
	assertType('bool', isset($p['r']['a']['b']));
	assertType('int|null', isset($p['r']['a']['b']) ? $p['r']['a']['b'] : null);

	return isset($p['r']['a']['b']) ? $p['r']['a']['b'] : null;
}
