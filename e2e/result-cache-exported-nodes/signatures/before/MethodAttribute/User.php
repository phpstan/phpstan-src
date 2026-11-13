<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\MethodAttribute;

function user(Foo $foo): void
{
	$foo->value();
}
