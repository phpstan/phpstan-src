<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassTraitAliasMethod;

function user(Foo $foo): int
{
	return $foo->number();
}
