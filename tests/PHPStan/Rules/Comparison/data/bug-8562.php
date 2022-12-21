<?php declare(strict_types = 1);

namespace Bug8562;

/**
 * @param array<int, bool> $a
 */
function a(array $a): void {
	$l = (string) array_key_last($a);
	$s = substr($l, 0, 2);
	if ($s === '') {
		;
	} else {
		var_dump($s);
	}
}


