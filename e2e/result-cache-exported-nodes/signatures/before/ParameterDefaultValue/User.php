<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ParameterDefaultValue;

function user(Foo $foo): int
{
	return $foo->identity();
}
