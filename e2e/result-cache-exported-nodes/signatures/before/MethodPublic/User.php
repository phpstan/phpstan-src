<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\MethodPublic;

function user(Foo $foo): int
{
	return $foo->value();
}
