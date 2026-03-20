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

	assertType('non-empty-array<int<0, 9>, array<int<0, 9>, string>>', $locations); // could be 'non-empty-array<int<0, 9>, non-empty-array<int<0, 9>, string>>'
	assertType('array<int<0, 9>, string>', $locations[0]); // could be 'non-empty-array<int<0, 9>, string>'
};
