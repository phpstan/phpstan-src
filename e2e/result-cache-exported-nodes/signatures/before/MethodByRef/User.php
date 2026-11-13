<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\MethodByRef;

function user(Foo $foo): mixed
{
	return array_pop($foo->values());
}
