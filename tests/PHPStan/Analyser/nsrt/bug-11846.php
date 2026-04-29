<?php

namespace Bug11846;

use function PHPStan\Testing\assertType;

function demo(): void
{
	$outerList = [];
	$idList = [1, 2];

	foreach ($idList as $id) {
		$outerList[$id] = [];
		array_push($outerList[$id], []);
	}
	assertType('array{1: array{array{}}, 2: array{array{}}}', $outerList);

	foreach ($outerList as $key => $outerElement) {
		$result = false;

		assertType('array{array{}}', $outerElement);
		foreach ($outerElement as $innerElement) {
			$result = true;
		}
		assertType('true', $result);

	}
}

/**
 * @param non-empty-list<1|2> $idList
 */
function demo2(array $idList): void
{
	$outerList = [];

	foreach ($idList as $id) {
		$outerList[$id] = [];
		array_push($outerList[$id], []);
	}
	assertType('non-empty-array{1?: array{array{}}, 2?: array{array{}}}', $outerList);

	foreach ($outerList as $key => $outerElement) {
		$result = false;

		assertType('array{array{}}', $outerElement);
		foreach ($outerElement as $innerElement) {
			$result = true;
		}
		assertType('true', $result);

	}
}
