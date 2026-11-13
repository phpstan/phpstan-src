<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ParameterPhpDocAdded;

function user(Foo $foo): string
{
	return $foo->values;
}
