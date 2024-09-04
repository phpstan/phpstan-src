<?php declare(strict_types = 1);

$array = [1,2,3];

uasort(
	$array,
	fn (string $one, string $two) => 1
);
