<?php

namespace Bug11912;

/**
 * @param array<string, mixed> $results
 * @param list<string> $names
 */
function appendResults(array $results, array $names): bool {
	// Make sure 'names' comes first in array
	$results = ['names' => $names] + $results;
	\PHPStan\Testing\assertType("list<string>", $results['names']);
	return true;
}
