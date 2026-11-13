<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\FunctionAssert;

function user(mixed $value): bool
{
	assertFoo($value);

	return $value instanceof Bar;
}
