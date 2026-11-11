<?php declare(strict_types=1);

namespace ArrayMapUnpackedArgsRule;

/**
 * @param list<list<int>> $lists
 */
function doFoo(array $lists, array $untyped): void
{
	array_map(fn (int $a, int $b) => $a + $b, ...$lists);
	array_map(fn ($a) => $a, ...$untyped);
	array_map(fn (int $a, int $b) => $a + $b, [1, 2], ...$lists);
	array_map(fn (string $a, string $b) => $a . $b, ...[[1, 2], [3, 4]]);
}
