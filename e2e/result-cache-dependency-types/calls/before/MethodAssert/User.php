<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\MethodAssert;

function user(Asserter $asserter, mixed $value): bool
{
	$asserter->isFoo($value);

	return $value instanceof Bar;
}
