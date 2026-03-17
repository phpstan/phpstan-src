<?php declare(strict_types = 1);

namespace Bug14312b;

function get_something(): mixed { return null; }

function test(string $needle): bool {
	$o = get_something();
	if (array_search($needle, $o) !== false) {
		if (array_key_exists($needle, $o)) {
			return true;
		}
	}
	return false;
}
