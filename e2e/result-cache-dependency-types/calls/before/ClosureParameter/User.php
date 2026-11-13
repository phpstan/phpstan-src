<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\ClosureParameter;

function user(): \Closure
{
	return function (Foo $foo): bool {
		return $foo instanceof Bar;
	};
}
