<?php declare(strict_types = 1);

namespace Bug14446Rule;

function test(bool $initial): void {
	$current = $initial;

	while (true) {
		if (!$current) {
			break;
		}

		$items = [1];
		foreach ($items as $item) {
			$current = false;
		}
	}

	var_dump($initial === true);
}

function testMaybeIterable(bool $initial): void {
	$current = $initial;

	while (true) {
		if (!$current) {
			break;
		}

		$items = rand() > 0 ? [1] : [];
		foreach ($items as $item) {
			$current = false;
		}
	}

	var_dump($initial === true);
}
