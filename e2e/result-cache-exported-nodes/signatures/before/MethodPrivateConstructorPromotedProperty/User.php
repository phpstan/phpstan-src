<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\MethodPrivateConstructorPromotedProperty;

function user(Foo $foo): int
{
	return $foo->x;
}
