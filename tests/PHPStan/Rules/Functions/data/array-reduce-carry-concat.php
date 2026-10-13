<?php // lint >= 8.0

namespace ArrayReduceCarryConcat;

/**
 * @param list<int> $integers
 */
function concat(array $integers): void
{
	array_reduce($integers, fn ($carry, $value) => $carry . $value, 'a');
	array_reduce($integers, function ($carry, $value) {
		return $carry . $value;
	}, 'a');
	array_reduce($integers, fn (string $carry, int $value): string => $carry . $value, '#');
	array_reduce(initial: 'a', callback: fn ($carry, $value) => $carry . $value, array: $integers);
}
