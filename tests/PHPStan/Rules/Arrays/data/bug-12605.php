<?php

namespace Bug12605;

/**
 * @return list<int>
 */
function test(): array
{
	return [];
}

function doFoo(): void {
	$test = test();

	if (isset($test[3])) {
		echo $test[1];
	}
	echo $test[1];
}

function doFooBar(): void {
	$test = test();

	if (isset($test[4])) {
		echo $test[10];
	}
}

function doBaz(): void {
	$test = test();

	if (array_key_exists(5, $test) && is_int($test[5])) {
		echo $test[3];
	}
}

