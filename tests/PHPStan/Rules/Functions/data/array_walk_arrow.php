<?php declare(strict_types = 1);  // lint >= 7.4

$array = ['foo' => 1, 'bar' => 2];
array_walk(
	$array,
	fn(stdClass $in, float $key): string => ''
);

$array = ['foo' => 1, 'bar' => 2];
array_walk(
	$array,
	fn(int $in, string $key, int $extra): string => '',
	'extra'
);

$array = ['foo' => 1, 'bar' => 2];
array_walk(
	$array,
	fn(int $value, string $key, int $extra): string => ''
);
