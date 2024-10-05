<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug10396;

function test(): string|null {
	$flags = \PREG_OFFSET_CAPTURE | \PREG_UNMATCHED_AS_NULL;
	return preg_replace_callback('/bar/', callback(...), 'foobar', -1, $count, $flags);
}

/**
 * @param array<int|string, array{0: string|null, 1: int}> $match
 * @return string
 */
function callback(array $match): string {
	return (string) $match[0][1];
}
