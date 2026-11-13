<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\FunctionClosureThis;

/**
 * @param-closure-this Foo $callback
 */
function run(\Closure $callback): void
{
}
