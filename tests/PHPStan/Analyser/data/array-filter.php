<?php

namespace ArrayFilter;

use function PHPStan\Testing\assertType;

function withoutAnyArgs(): void
{
	$filtered1 = array_filter();
	assertType('array', $filtered1);
}

/**
 * @param $var1 $mixed
 */
function withMixedInsteadOfArray($var1): void
{
	$filtered1 = array_filter($var1);
	assertType('(array|null)', $filtered1);
}

/**
 * @param array<string, bool|float|int|string> $map1
 * @param array<string, bool|float|int|string> $map2
 * @param array<string, bool|float|int|string> $map3
 */
function withoutCallback(array $map1, array $map2, array $map3): void
{
	$filtered1 = array_filter($map1);
	assertType('array<string, float|int<min, -1>|int<1, max>|non-empty-string|true>', $filtered1);

	$filtered2 = array_filter($map2, null, ARRAY_FILTER_USE_KEY);
	assertType('array<string, float|int<min, -1>|int<1, max>|non-empty-string|true>', $filtered2);

	$filtered3 = array_filter($map3, null, ARRAY_FILTER_USE_BOTH);
	assertType('array<string, float|int<min, -1>|int<1, max>|non-empty-string|true>', $filtered3);
}
