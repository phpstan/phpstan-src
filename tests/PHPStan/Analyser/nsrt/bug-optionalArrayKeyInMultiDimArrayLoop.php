<?php declare(strict_types = 1);

namespace OptionalArrayKeyInMultiDimArrayLoop;

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

		\PHPStan\Testing\assertType('array{foo: 5, bar: 5, amount?: 0.0}', $itemMap[$x][$month]);
		$itemMap[$x][$month]['amount'] ??= 0.0;
	}

	return $itemMap;
}
