<?php

declare(strict_types=1);

namespace Bug9803;

use function PHPStan\Testing\assertType;

function doFoo() {
	$random = rand(1, 5);
	$array = array("one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "ten");

	$keys = array();
	if ($random == 1) {
		$keys = array(array_rand($array));
		assertType('array{int}', $keys);
	}
	else {
		$keys = array_rand($array, $random);
		assertType('array<int, int>', $keys);
	}

	assertType('array<int, int>', $keys);
	$theKeys = array_keys($keys);
	assertType('list<int>', $theKeys);
}


