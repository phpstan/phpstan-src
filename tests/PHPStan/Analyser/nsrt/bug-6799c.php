<?php declare(strict_types = 1);

namespace Bug6799C;

use function PHPStan\Testing\assertType;

// https://3v4l.org/g5UjS

$x = null;
assertType('null', $x);
$a = [&$x];
assertType('mixed', $x); // Could stay null

function doFoo(array &$arr) {
	$arr[0] = 'string';
}

doFoo($a);
assertType('mixed', $x);
