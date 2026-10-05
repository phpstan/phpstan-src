<?php // lint >= 8.6

declare(strict_types = 1);

namespace Bug15372;

use function PHPStan\Testing\assertType;

/**
 * @param list<int|null> $list
 */
function doFoo(array $list): void
{
	assertType('array<int<0, max>, int>', array_filter($list, static fn ($item): bool => $item !== null, ARRAY_FILTER_USE_VALUE));
	assertType('array<int<0, max>, int>', array_filter($list, static fn ($item): bool => $item !== null, mode: ARRAY_FILTER_USE_VALUE));
	assertType('array<int<0, max>, int>', array_filter($list, static fn ($item): bool => $item !== null, 0));
}
