<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\StaticMethodAssert;

function user(mixed $value): bool
{
	Asserter::isFoo($value);

	return $value instanceof Bar;
}
