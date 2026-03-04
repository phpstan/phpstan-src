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
		echo $value; // SHOULD report "is not defined"
	}
}

function boo(): void {
	$key = rand(0, 2);

	if ($key === 1) {
		$value = 'test';
	}

	if ($key === 1) {
		unset($value);
	}

	if ($key === 1) {
		echo $value;
	}
}
