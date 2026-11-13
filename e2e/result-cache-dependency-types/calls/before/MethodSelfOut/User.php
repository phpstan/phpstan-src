<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\MethodSelfOut;

function user(Builder $builder): bool
{
	$builder->mark();

	return $builder instanceof Bar;
}
