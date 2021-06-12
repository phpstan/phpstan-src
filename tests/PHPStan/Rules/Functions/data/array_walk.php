<?php declare(strict_types = 1);

$array = ['foo' => 1, 'bar' => 2];
array_walk(
	$array,
	fn(stdClass $in, float $key) => ''
);

$array = ['foo' => 1, 'bar' => 2];
array_walk(
	$array,
	fn(int $in, string $key, int $extra) => '',
	'extra'
);
