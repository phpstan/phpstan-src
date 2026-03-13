<?php declare(strict_types = 1);

namespace Bug6799C;

use function PHPStan\Testing\assertType;

// https://3v4l.org/g5UjS

$a = [&$x];
function doFoo(array &$arr) {
	$arr[0] = 'string';
}

var_dump($x);
assertType('mixed', $x);
doFoo($a);
var_dump($x);
assertType('mixed', $x); // could be string
