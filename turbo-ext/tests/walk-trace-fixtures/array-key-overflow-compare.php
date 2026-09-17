<?php declare(strict_types = 1);

// walk-trace fixture: an explicit PHP_INT_MAX key repeated after the next
// implicit index became unpredictable, in a nested literal, then a string key
// and finally an implicit item
function arrayKeyOverflowCompare(): array
{
	$e = ['x' => [9223372036854775807 => 1, 9223372036854775807 => 2, 'k' => 3], [9223372036854775807 => 'a', 'b' => 'c']];
	$f = [[9223372036854775807 => 1, 9223372036854775807 => 2, 'k' => 3, 4]];
	return [$e, $f];
}
