<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ParameterType;

function user(Foo $foo): void
{
	$foo->take(1);
}
