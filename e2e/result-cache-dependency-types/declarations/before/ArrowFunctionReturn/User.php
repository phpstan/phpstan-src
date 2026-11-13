<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\ArrowFunctionReturn;

function user(): \Closure
{
	return static fn (): ?Foo => null;
}
