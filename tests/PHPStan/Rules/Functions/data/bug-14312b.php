<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug14312b;

function get_something(): mixed { return null; }

function test(string $needle): bool {
	$o = get_something();
	if (array_search($needle, $o) !== false) {
		if (array_search($needle, $o)) {
			return true;
		}
	}
	return false;
}
