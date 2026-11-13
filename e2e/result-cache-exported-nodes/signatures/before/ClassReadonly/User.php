<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassReadonly;

function user(Foo $foo): void
{
	$foo->x = 2;
}
