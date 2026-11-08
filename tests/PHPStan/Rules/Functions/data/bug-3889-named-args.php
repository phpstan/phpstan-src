<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug3889NamedArgs;

function doFoo(string $s): void
{
	str_replace(search: ['a', 'b'], subject: $s, replace: ['x']);
	str_replace(search: ['a', 'b'], subject: $s, replace: ['x', 'y']);
}
