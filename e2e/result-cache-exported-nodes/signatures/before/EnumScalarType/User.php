<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\EnumScalarType;

function user(Foo $foo): bool
{
	return $foo instanceof \BackedEnum;
}
