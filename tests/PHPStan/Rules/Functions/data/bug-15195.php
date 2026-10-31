<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug15195;

/**
 * @param list<string> $arr
 */
function foo(
	array $arr,
): void {
	$arr1 = array_filter(
		array: $arr,
		callback: static fn(string $s): bool => $s !== '',
	);

	$arr2 = array_filter(
		callback: static fn(string $s): bool => $s !== '',
		array: $arr,
	);
}
