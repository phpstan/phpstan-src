<?php

namespace Bug14223;

use function PHPStan\Testing\assertType;

/**
 * @param list<string> $a1
 */
function doFoo(array $a1): void
{
	$a2 = array_count_values($a1);
	assertType('array<string, int<1, max>>', $a2);
}

/**
 * @param non-empty-list<string> $a1
 */
function doBar(array $a1): void
{
	$a2 = array_count_values($a1);
	assertType('non-empty-array<string, int<1, max>>', $a2);
}
