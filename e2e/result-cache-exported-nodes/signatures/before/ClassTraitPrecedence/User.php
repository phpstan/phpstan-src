<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassTraitPrecedence;

function user(Foo $foo): int
{
	return $foo->value();
}
