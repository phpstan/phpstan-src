<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\MethodStatic;

function user(): int
{
	return Foo::value();
}
