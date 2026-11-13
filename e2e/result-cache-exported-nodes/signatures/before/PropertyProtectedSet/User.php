<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\PropertyProtectedSet;

function user(Foo $foo): void
{
	$foo->value = 1;
}
