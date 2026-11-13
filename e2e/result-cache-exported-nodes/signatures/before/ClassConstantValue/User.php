<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassConstantValue;

function user(): bool
{
	return Foo::VALUE === 1;
}
