<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\NewClassString;

function user(): bool
{
	$class = 'ResultCacheE2EDependencyTypes\NewClassString\Foo';

	return new $class() instanceof Bar;
}
