<?php declare(strict_types=1);

namespace Bug5760;

/**
 * @param list<int>|null $arrayOrNull
 */
function doImplode(?array $arrayOrNull): void
{
	join(',', $arrayOrNull);
	join($arrayOrNull);

	implode(',', $arrayOrNull);
	implode($arrayOrNull);
}

/**
 * @param array<string>|string $union
 */
function more(array|string $union): void
{
	join(',', $union);
	join($union);

	implode(',', $union);
	implode($union);
}

function success(): void
{
	join(',', ['']);
	join(['']);

	implode(',', ['']);
	implode(['']);
}
