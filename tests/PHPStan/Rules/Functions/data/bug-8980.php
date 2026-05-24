<?php declare(strict_types = 1);

namespace Bug8980;

function doFoo():void {
	$func = rand(0,1) ? 'funcA' : 'funcB';

	if (!function_exists($func)) {
		throw new \Exception();
	}

	// the function_exists() will only assure one of the functions to exist.
	funcA();
	funcB();
	$func();
}
