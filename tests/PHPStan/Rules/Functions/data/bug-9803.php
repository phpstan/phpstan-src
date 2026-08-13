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
		assertType('array{0|1|2|3|4|5|6|7|8|9}', $keys);
	}
	else {
		$keys = array_rand($array, $random);
		assertType('non-empty-list<0|1|2|3|4|5|6|7|8|9>', $keys);
	}

	assertType('non-empty-list<0|1|2|3|4|5|6|7|8|9>', $keys);
	$theKeys = array_keys($keys);
	assertType('non-empty-list<int<0, max>>', $theKeys);
}


