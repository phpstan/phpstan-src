<?php

namespace PR4390;

use function PHPStan\Testing\assertType;

function (string $s): void {
	$locations = [];
	for ($i = 0; $i < 10; $i++) {
		$locations[$i] = [];
		for ($j = 0; $j < 10; $j++) {
			$locations[$i][$j] = $s;
		}
	}

	assertType('non-empty-array<int<0, 9>, list<string>>', $locations);
	assertType('list<string>', $locations[0]);
};
