<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\PhpDocUses;

function user(Foo $foo): int
{
	return $foo->box()->value();
}
