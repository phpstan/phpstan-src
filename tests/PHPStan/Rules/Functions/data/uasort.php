<?php declare(strict_types = 1);

$array = [1,2,3];

uasort(
	$array,
	function (string $one, string $two) {
		return 1;
	}
);
