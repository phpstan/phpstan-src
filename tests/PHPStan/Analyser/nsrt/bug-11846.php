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
	assertType('non-empty-array<1|2, array{}|array{array{}}>', $outerList);

	foreach ($outerList as $key => $outerElement) {
		$result = false;

		assertType('array{}|array{array{}}', $outerElement);
		foreach ($outerElement as $innerElement) {
			$result = true;
		}
		assertType('bool', $result); // could be 'true'

	}
}
