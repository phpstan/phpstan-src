<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassTraitAlias;

function user(Foo $foo): string
{
	return $foo->greet();
}
