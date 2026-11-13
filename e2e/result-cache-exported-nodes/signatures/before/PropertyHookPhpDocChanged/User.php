<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\PropertyHookPhpDocChanged;

function user(Foo $foo): void
{
	$foo->value = -1;
}
