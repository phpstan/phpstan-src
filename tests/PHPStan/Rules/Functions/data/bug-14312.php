<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug14312;

function get_something(): mixed { return null; }

function test(string $a, string $b): bool {
	$o = get_something();
	if (array_key_exists($a, $o)) {
		if (array_key_exists($b, $o)) {
			return true;
		}
	}
	return false;
}
