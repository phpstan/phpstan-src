<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\ClosureParameter;

function user(): \Closure
{
	return static function (?Foo $foo): void {
	};
}
