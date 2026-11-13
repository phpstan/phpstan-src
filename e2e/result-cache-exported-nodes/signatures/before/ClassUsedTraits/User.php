<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassUsedTraits;

function user(Foo $foo): int
{
	return $foo->value();
}
