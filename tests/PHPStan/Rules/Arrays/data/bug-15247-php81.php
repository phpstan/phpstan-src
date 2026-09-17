<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15247DuplicateKeysPhp81;

function stringKeysSpread(): array
{
	return [...['a' => 1, 'b' => 2], 0 => 'x'];
}

function stringKeysSpreadDuplicate(): array
{
	return ['a' => 1, ...['a' => 2]];
}

function mixedKeysSpread(): array
{
	return [...['a' => 1, 5, 'b' => 2, 6], 1 => 'x'];
}
