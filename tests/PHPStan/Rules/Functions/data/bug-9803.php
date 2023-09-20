<?php

declare(strict_types=1);

namespace Bug9803;

function doFoo() {
	$random = rand(1, 5);
	$array = array("one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "ten");

	$keys = array();
	if ($random == 1)
		$keys = array(array_rand($array));
	else
		$keys = array_rand($array, $random);

	$theKeys = array_keys($keys);
}


