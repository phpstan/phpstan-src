<?php declare(strict_types = 1);

$array = ['foo' => 1, 'bar' => 2];
array_walk(
	$array,
	function(stdClass $in, float $key): string {
		return '';
	}
);

$array = ['foo' => 1, 'bar' => 2];
array_walk(
	$array,
	function(int $in, string $key, int $extra): string {
		return '';
	},
	'extra'
);

$array = ['foo' => 1, 'bar' => 2];
array_walk(
	$array,
	function(int $value, string $key, int $extra): string {
		return '';
	}
);
