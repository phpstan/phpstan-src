<?php

namespace Bug2294;

use function PHPStan\Testing\assertType;

// Removing one of the entries makes the error go away

$entries = ['A' => null, 'B' => null];
foreach($entries as $key => $value) {
	$entries[$key] = ['a' => 1, 'b' => 2];
}
assertType('array{A: array{a: 1, b: 2}|null, B: array{a: 1, b: 2}|null}', $entries);
// Uncommenting the next line does NOT make the error go away
//$entries['A'] = ['a' => 1, 'b' => 2];

// Removing one of these lines also makes the error go away
$entries['A']['a'] += 1;
$entries['A']['b'] += 1;
assertType('array{A: array{a: 2, b: 3}, B: array{a: 1, b: 2}|null}', $entries);
