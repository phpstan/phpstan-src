<?php declare(strict_types = 1);

namespace Bug2294;

use function PHPStan\Testing\assertType;

function (): void {
	$entries = ['A' => null, 'B' => null];

	$entries2 = [];
	foreach($entries as $key => $value) {
		$entries2[$key] = ['a' => 1, 'b' => 2];
	}
	assertType("non-empty-array<'A'|'B', array{a: 1, b: 2}>", $entries2);
};
