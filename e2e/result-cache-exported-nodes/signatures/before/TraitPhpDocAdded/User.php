<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\TraitPhpDocAdded;

function user(UsesFoo $usesFoo): int
{
	return $usesFoo->magic();
}
