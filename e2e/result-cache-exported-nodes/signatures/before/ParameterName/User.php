<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ParameterName;

function user(Foo $foo): void
{
	$foo->take(value: 1);
}
