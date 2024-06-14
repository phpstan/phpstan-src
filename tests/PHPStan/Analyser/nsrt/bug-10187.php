<?php declare(strict_types = 1);

namespace Bug10187;

use function PHPStan\Testing\assertType;

function inc(string $n): string
{
	$before = $n;
	$after = ++$n;
	assertType('array{n: (float|int|string), before: string, after: (float|int|string)}', compact('n', 'before', 'after'));

	return (string)$after; // No warnings expected here
}
