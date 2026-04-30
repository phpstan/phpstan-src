<?php

namespace Bug14560TypeInference;

use function PHPStan\Testing\assertType;

/**
 * Verify that oversized-array generalization in TypeCombinator::optimizeConstantArrays
 * produces types that accept empty sub-arrays and don't over-generalize nested values.
 */
function oversizedWithEmptySubArrays(): void
{
	$items = [];

	if (rand()) {
		$items[] = ['kind' => 'a', 'data' => [['x' => 1]], 'extra' => []];
	}
	if (rand()) {
		$items[] = ['kind' => 'b', 'data' => [['x' => 2]], 'extra' => []];
	}
	if (rand()) {
		$items[] = ['kind' => 'c', 'data' => [['x' => 3]], 'extra' => []];
	}
	if (rand()) {
		$items[] = ['kind' => 'd', 'data' => [['x' => 4]], 'extra' => []];
	}
	if (rand()) {
		$items[] = ['kind' => 'e', 'data' => [['x' => 5]], 'extra' => []];
	}
	if (rand()) {
		$items[] = ['kind' => 'f', 'data' => [['x' => 6]], 'extra' => []];
	}
	if (rand()) {
		$items[] = ['kind' => 'g', 'data' => [['x' => 7]], 'extra' => []];
	}
	if (rand()) {
		$items[] = ['kind' => 'h', 'data' => [['x' => 8]], 'extra' => []];
	}

	if ($items === []) {
		return;
	}

	foreach ($items as $item) {
		// The type of $item['extra'] must accept array{} — the empty array that
		// every branch actually writes. Before the fix, optimizeConstantArrays
		// would tag it with OversizedArrayType, producing array{}&oversized-array
		// which contradicts itself (empty but oversized).
		assertType('array{}', $item['extra']);
	}
}
