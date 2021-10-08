<?php declare(strict_types=1);

namespace Bug5760;

/**
 * @param list<int>|null $arrayOrNull
 */
function doImplode(?array $arrayOrNull): void
{
	join(',', $arrayOrNull);
	implode(',', $arrayOrNull);
	implode($arrayOrNull);
	join($arrayOrNull);
}
