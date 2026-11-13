<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\FunctionParamOut;

/**
 * @param-out Foo $value
 */
function fill(mixed &$value): void
{
	$value = new Foo();
}
