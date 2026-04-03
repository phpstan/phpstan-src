<?php declare(strict_types = 1);

namespace Bug11218;

function doFoo() {
	$level = 'test';

	for ($i = 0 ; $i <= 3 ; $i++) {
		if ($i === 0) {
			$test[$level] = 'this is a';
		} else {
			$test[$level] .= ' test';
		}
	}
}

function doBar() {
	$level = 'test';

	$test = [];

	for ($i = 0 ; $i <= 3 ; $i++) {
		if ($i === 0) {
			$test[$level] = 'this is a';
		} else {
			$test[$level] .= ' test';
		}
	}
}
