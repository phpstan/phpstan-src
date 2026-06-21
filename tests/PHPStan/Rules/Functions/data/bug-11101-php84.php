<?php

namespace Bug11101Php84;

function doFoo(array $array): void
{
	// pure callbacks - reported as no effect
	array_any($array, 'is_string');
	array_all($array, 'is_string');
}

function doBar(array $array): void
{
	// impure callbacks - NOT reported
	array_any($array, function ($v) {
		echo $v;
		return true;
	});
	array_all($array, function ($v) {
		echo $v;
		return true;
	});
}
