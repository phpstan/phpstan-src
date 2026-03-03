<?php declare(strict_types = 1);

namespace Bug14117;

function foo(): void {
	$key = rand(0, 2);

	if ($key === 2) {
		$value = 'test';
	}

	if ($key === 1) {
		$value = 'test';
	}

	if ($key === 1) {
		echo $value;
	}
}

function bar(): void {
	$key = rand(0, 2);

	if ($key === 2) {
		$value = 'two';
	}

	if ($key === 1) {
		$value = 'one';
	}

	if ($key === 2) {
		echo $value;
	}
}

function baz(): void {
	$key = rand(0, 3);

	if ($key === 1) {
		$value = 'one';
	}

	if ($key === 2) {
		$value = 'two';
	}

	if ($key === 3) {
		echo $value; // this one SHOULD report "might not be defined" because $key === 3 doesn't guarantee either earlier block ran
	}
}
