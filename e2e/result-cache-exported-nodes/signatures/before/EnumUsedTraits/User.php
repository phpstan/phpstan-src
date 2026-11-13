<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\EnumUsedTraits;

function user(Foo $foo): string
{
	return $foo->hello();
}
