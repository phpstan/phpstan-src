<?php declare(strict_types = 1);

namespace Bug14660;

use function PHPStan\Testing\assertType;

function test_with_forward_goto(): void {
	$id = null;
	if (random_int(0, 1))
		goto fin;
	$id = 1;
	fin:
	assertType('1|null', $id);
}

function test_with_backward_goto(): void {
	$ok = false;
	retry:
	assertType('bool', $ok);
	if (!$ok) {
		$ok = (bool) random_int(0, 1);
		goto retry;
	}
}
