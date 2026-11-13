<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ParameterPhpDocRemoved;

function user(Foo $foo): string
{
	return $foo->values;
}
