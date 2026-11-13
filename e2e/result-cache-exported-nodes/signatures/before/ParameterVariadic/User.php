<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ParameterVariadic;

function user(Foo $foo): void
{
	$foo->take(1, 2);
}
