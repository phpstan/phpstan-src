<?php

namespace Bug12364;

use function PHPStan\Testing\assertType;

/** @return array{x: string, y?: string, z?: string} */
function foo(): array {
	return [ 'x' => 'foo' ];
}

$x = $y = null;
assertType('null', $x);
assertType('null', $y);
extract(foo());
assertType('string', $x);
assertType('string|null', $y); // <-- should be: null|string
assertType('mixed', $z);
var_dump($x);
var_dump($y); // <-- does exist

/** @return array{xx: string, yy?: string} */
function foo2(): array {
	return [ 'xx' => 'foo' ];
}

function testUndefined()
{
	if (rand(0, 1)) {
		$xx = $yy = 0;
		assertType('0', $xx);
		assertType('0', $yy);
	}

	extract(foo2());
	assertType('string', $xx);

	if (isset($yy)) {
		assertType('0|string', $yy);
	}
}
