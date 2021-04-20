<?php

namespace BitwiseNot;

use function PHPStan\Testing\assertType;

/**
 * @param string|int $stringOrInt
 */
function foo(int $int, string $string, float $float, $stringOrInt) : void{
	assertType('int', ~$int);
	assertType('string', ~$string);
	assertType('int', ~$float);
	assertType('int|string', ~$stringOrInt);
	assertType("'" . (~"abc") . "'", ~"abc");
	assertType('int', ~1); //result is dependent on PHP_INT_SIZE
}
