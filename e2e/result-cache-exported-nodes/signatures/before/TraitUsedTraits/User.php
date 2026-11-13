<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\TraitUsedTraits;

function user(UsesFoo $usesFoo): int
{
	return $usesFoo->value();
}
