<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug3889NamedArgs;

function doFoo(string $s, bool $b): void
{
	str_replace(search: ['a', 'b'], subject: $s, replace: ['x']);
	str_replace(search: ['a', 'b'], subject: $s, replace: ['x', 'y']);
	str_replace(search: ['a', 'b'], subject: $s, replace: []);

	$r = $b ? [] : ['x'];
	str_replace(['a', 'b', 'c'], $r, $s);

	$r = $b ? ['x'] : ['x', 'y', 'z'];
	str_replace(['a', 'b', 'c'], $r, $s);

	str_replace(['a', 'b', 'c'], ['x', 'y', 'z'], $s);
}
