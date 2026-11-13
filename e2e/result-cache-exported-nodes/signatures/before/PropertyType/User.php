<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\PropertyType;

function user(Foo $foo): string
{
	return $foo->value;
}
