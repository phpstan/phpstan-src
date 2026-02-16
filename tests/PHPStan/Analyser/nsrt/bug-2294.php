<?php declare(strict_types = 1);

namespace Bug2294;

use function PHPStan\Testing\assertType;

function () {
	$entries = ['A' => null, 'B' => null];

	$entries2 = [];
	foreach ($entries as $key => $value) {
		$entries2[$key] = ['a' => 1, 'b' => 2];
	}
	assertType('array{A: array{a: 1, b: 2}, B: array{a: 1, b: 2}}', $entries2);

	$entries2['A']['a'] += 1;
	assertType('array{A: array{a: 2, b: 2}, B: array{a: 1, b: 2}}', $entries2);

	$entries2['A']['b'] += 1;
	assertType('array{A: array{a: 2, b: 3}, B: array{a: 1, b: 2}}', $entries2);
};
