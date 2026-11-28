<?php

namespace ResetAfterNonEmptyLoop;

use function PHPStan\Testing\assertType;

/**
 * @param list<string> $joins
 */
function mergeJoins(array $joins, string $s, string $hash): void
{
	if (count($joins) === 0) return;
	assertType('non-empty-list<string>', $joins);

	/** @var array<array<string>> $aggregated */
	$aggregated = [];
	foreach ($joins as $join) {
		$aggregated[$s][$hash] = $s;
	}
	// the declared type lets the elements not written in the loop be empty
	assertType('non-empty-array<array<string>>', $aggregated);

	foreach ($aggregated as $sameJoins) {
		$first = reset($sameJoins);
		assertType('string|false', $first);
	}
}
