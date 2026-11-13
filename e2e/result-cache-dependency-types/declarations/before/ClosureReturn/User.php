<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\ClosureReturn;

function user(): \Closure
{
	return static function (): ?Foo {
		return null;
	};
}
