<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15372;

function (array $a): void {
	array_filter($a, fn ($v) => $v > 1, ARRAY_FILTER_USE_VALUE);
	array_filter($a, fn ($v) => $v > 1, mode: ARRAY_FILTER_USE_VALUE);
	array_filter($a, fn ($k) => $k > 1, ARRAY_FILTER_USE_KEY);
	array_filter($a, fn ($v, $k) => $v > $k, ARRAY_FILTER_USE_BOTH);
	array_filter($a, fn ($v) => $v > 1, SORT_REGULAR);
};
