<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassConstantPhpDocChanged;

function user(Foo $foo): string
{
	return $foo::VALUE;
}
