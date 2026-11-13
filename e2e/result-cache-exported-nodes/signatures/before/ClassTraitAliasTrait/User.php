<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassTraitAliasTrait;

function user(Foo $foo): int
{
	return $foo->number();
}
