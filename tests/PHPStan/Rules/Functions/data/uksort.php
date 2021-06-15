<?php declare(strict_types = 1);

$array = ['one' => 1, 'two' => 2, 'three' => 3];

uksort(
	$array,
	function (stdClass $one, stdClass $two): int {
		return 1;
	}
);
