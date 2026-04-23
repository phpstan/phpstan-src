<?php declare(strict_types = 1);

namespace Bug2294;

// Removing one of the entries makes the error go away
$entries = ['A' => null, 'B' => null];
foreach($entries as $key => $value) {
	$entries[$key] = ['a' => 1, 'b' => 2];
}
// Uncommenting the next line does NOT make the error go away
//$entries['A'] = ['a' => 1, 'b' => 2];

// Removing one of these lines also makes the error go away
$entries['A']['a'] += 1;
$entries['A']['b'] += 1;
