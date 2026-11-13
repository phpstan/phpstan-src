<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\ArrowFunctionParameter;

function user(): \Closure
{
	return static fn (Foo $foo): bool => $foo instanceof Bar;
}
