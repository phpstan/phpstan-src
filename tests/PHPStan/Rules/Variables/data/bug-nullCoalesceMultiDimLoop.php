<?php declare(strict_types = 1);

namespace BugNullCoalesceMultiDimLoop;

/**
 * @param array<int, array{1: int, 2: int, 3: float}> $rows
 */
function foo(array $rows): mixed
{
	$itemMap = [];

	foreach ($rows as $row) {
		$x = $row[1];
		$month = $row[2];

		$itemMap[$x][$month]['foo'] ??= 5;
		$itemMap[$x][$month]['bar'] ??= 5;

		$itemMap[$x][$month]['amount'] ??= 0.0;
	}

	return $itemMap;
}
