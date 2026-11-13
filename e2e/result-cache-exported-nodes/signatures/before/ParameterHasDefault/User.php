<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ParameterHasDefault;

function user(Foo $foo): void
{
	$foo->take();
}
