<?php declare(strict_types = 1);

namespace Bug14227;

function foo(): void {
	$key = rand(0, 2);

	if ($key === 1) {
		$value = 'test';
	}

	if ($key === 2) {
		unset($value);
	}

	if ($key === 1) {
		echo $value; // should not report "might not defined"
	}
}

function bar(): void {
	$key = rand(0, 2);

	if ($key === 1) {
		$value = 'test';
	}

	if ($key !== 0) {
		unset($value);
	}

	if ($key === 1) {
		echo $value; // SHOULD report - unset also runs when $key === 1
	}
}
