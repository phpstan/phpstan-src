<?php declare(strict_types = 1);

namespace Bug13799;

/**
 * @phpstan-impure
 * @return list<'a'|'b'>
 */
function get_whitelist(): array {
	$s = [];
	if (rand(0, 1)) {
		$s[] = 'a';
	}
	if (rand(0, 1)) {
		$s[] = 'b';
	}
	return $s;
}

if (in_array('a', get_whitelist(), true)) {
	echo 'ok';
}

if (in_array('c', get_whitelist(), true)) {
	echo 'ok';
}
