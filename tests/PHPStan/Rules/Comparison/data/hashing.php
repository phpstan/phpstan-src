<?php

namespace Hashing;

use function md5;
use function md5_file;

function doFoo(string $s):void {
	if (md5($s) === 'ABC') {

	}
	if (md5_file($s) === 'ABC') {

	}
}

/**
 * @param (non-falsy-string&numeric-string)|(non-falsy-string&lowercase-string) $s
 * @return void
 */
function doFooBar($s) {
	if ($s === '598d4c200461b81522a3328565c25f7c') {

	}
	if ($s === 'a') {

	}
	if ($s === '123') {

	}
	if ($s === 'A') {

	}
}
