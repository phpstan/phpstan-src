<?php

namespace Bug11101;

function doFoo(array $array): void
{
	// pure callbacks - reported as no effect
	array_filter($array, 'is_string');
	array_map('is_string', $array);
	array_reduce($array, function ($carry, $item) {
		return $carry + $item;
	}, 0);
	array_filter($array);
	preg_replace_callback('/\d/', 'strtoupper', 'abc');
	preg_replace_callback_array(['/\d/' => 'strtoupper'], 'abc');
}

function doBar(array $array, callable $cb): void
{
	// impure callbacks - NOT reported
	array_filter($array, function ($v) {
		echo $v;
		return true;
	});
	array_map(function ($v) {
		echo $v;
		return $v;
	}, $array);
	array_reduce($array, function ($carry, $item) {
		echo $item;
		return $carry;
	}, 0);
	array_map('printf', $array);

	// unknown callable purity - NOT reported
	array_map($cb, $array);
	array_filter($array, $cb);
}
