<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\TraitPhpDocChanged;

function user(UsesFoo $usesFoo): int
{
	return $usesFoo->magic();
}
