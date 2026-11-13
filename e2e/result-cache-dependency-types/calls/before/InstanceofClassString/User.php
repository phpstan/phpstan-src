<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\InstanceofClassString;

function user(Bar $bar): bool
{
	$class = 'ResultCacheE2EDependencyTypes\InstanceofClassString\Foo';

	return $bar instanceof $class;
}
