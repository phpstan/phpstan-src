<?php

namespace Bug12364;

use function PHPStan\Testing\assertType;

/** @return array{x: string, y?: string} */
function foo(): array {
	return [ 'x' => 'foo' ];
}

$x = $y = null;
assertType('null', $x);
assertType('null', $y);
extract(foo());
assertType('string', $x);
assertType('string|null', $y); // <-- should be: null|string
var_dump($x);
var_dump($y); // <-- does exist
