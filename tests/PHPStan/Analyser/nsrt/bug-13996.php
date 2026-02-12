<?php declare(strict_types = 1);

namespace Bug13996;

use function PHPStan\Testing\assertType;

/**
 * @param array<string> $strings
 */
function strings(array $strings): void
{
	assertType('non-empty-array<int|string, int<1, max>>', array_count_values($strings));
}

/**
 * @param array<int> $ints
 */
function ints(array $ints): void
{
	assertType('non-empty-array<int, int<1, max>>', array_count_values($ints));
}

/**
 * @param array<int|string> $mixed
 */
function intOrString(array $mixed): void
{
	assertType('non-empty-array<int|string, int<1, max>>', array_count_values($mixed));
}
