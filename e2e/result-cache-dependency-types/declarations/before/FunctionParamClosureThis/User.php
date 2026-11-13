<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\FunctionParamClosureThis;

/**
 * @param-closure-this Foo $callback
 */
function user(\Closure $callback): void
{
}
