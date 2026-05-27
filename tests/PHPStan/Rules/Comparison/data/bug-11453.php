<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11453;

/** @return 1|2|3 */
function getInt(): int {
	return 1;
}

/** @return 'a'|'b'|'c' */
function getString(): string {
	return 'a';
}

function test(): void {
	$int = getInt();
	$string = getString();

	$type = match ([$int, $string]) {
		[1, 'a'] => 'one-a',
		[1, 'b'] => 'one-b',
		[1, 'c'] => 'one-c',

		[2, 'a'] => 'two-a',
		[2, 'b'] => 'two-b',
		[2, 'c'] => 'two-c',

		[3, 'a'] => 'three-a',
		[3, 'b'] => 'three-b',
		[3, 'c'] => 'three-c',
	};
}
