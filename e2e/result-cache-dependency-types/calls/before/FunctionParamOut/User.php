<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\FunctionParamOut;

function user(): bool
{
	fill($value);

	return $value instanceof Bar;
}
