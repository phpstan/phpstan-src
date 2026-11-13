<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\FunctionClosureThis;

function user(): void
{
	run(function (): bool {
		return $this instanceof Bar;
	});
}
