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

