<?php declare(strict_types=1);

namespace UnsetFalseKey;

/** @var array<int, int> $data */
unset($data[false]);

function test_remove_element(): void {
	$modified = [1, 4, 6, 8];

	// this would happen in the SUT
	unset($modified[array_search(4, $modified, true)]);
	unset($modified[array_search(5, $modified, true)]); // bug is here - will unset key `0` by accident

	assert([1, 6, 8] === $modified); // actually is [6, 8]
}
