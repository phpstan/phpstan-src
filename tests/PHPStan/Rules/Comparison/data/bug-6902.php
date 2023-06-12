<?php

namespace Bug6902\Comparison;

function doFoo()
{
	/** @var array * */
	$array1 = ['a' => 1, 'b' => 2];
	/** @var array * */
	$array2 = ['a' => 1];

	$check = function (string $key) use (&$array1, &$array2): bool {
		if (!isset($array1[$key], $array2[$key])) {
			return false;
		}
		// ... more conditions here ...
		return true;
	};

	if ($check('a')) {
		// ...
	}
}
