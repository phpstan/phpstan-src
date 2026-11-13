<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\EnumTraitAlias;

function user(Foo $foo): string
{
	return $foo->greet();
}
