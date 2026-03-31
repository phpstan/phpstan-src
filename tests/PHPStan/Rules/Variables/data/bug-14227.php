<?php declare(strict_types = 1);

namespace Bug14227;

function moo(): void {
	$key = rand(0, 2);

	if ($key === 1) {
		$value = 'test';
	}

	if ($key === 2) {
		unset($value);
	}

	if ($key === 1) {
		echo $value;
	}
}
