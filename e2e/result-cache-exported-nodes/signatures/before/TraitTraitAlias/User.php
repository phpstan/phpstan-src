<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\TraitTraitAlias;

function user(UsesFoo $usesFoo): int
{
	return $usesFoo->number();
}
