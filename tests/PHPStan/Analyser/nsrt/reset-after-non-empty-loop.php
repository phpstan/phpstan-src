<?php

namespace ResetAfterNonEmptyLoop;

use function PHPStan\Testing\assertType;

/**
 * @param list<string> $joins
 */
function mergeJoins(array $joins, string $s, string $hash): void
{
	if (count($joins) === 0) return;

	/** @var array<array<string>> $aggregated */
	$aggregated = [];
	foreach ($joins as $join) {
		$aggregated[$s][$hash] = $s;
	}

	foreach ($aggregated as $sameJoins) {
		$first = reset($sameJoins);
		assertType('string', $first);
	}
}
