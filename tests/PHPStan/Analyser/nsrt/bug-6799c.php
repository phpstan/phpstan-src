<?php declare(strict_types = 1);

namespace Bug6799C;

use function PHPStan\Testing\assertType;

// https://3v4l.org/g5UjS

$a = [&$x];
assertType('mixed', $x);

function doFoo(array &$arr) {
	$arr[0] = 'string';
}

doFoo($a);
assertType('mixed', $x);
