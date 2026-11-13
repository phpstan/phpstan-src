<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\PropertyHookParameter;

function user(Foo $foo): void
{
	$foo->value = '1';
}
