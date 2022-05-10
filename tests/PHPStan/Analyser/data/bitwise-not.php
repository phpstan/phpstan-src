<?php

namespace BitwiseNot;

use function PHPStan\Testing\assertType;

/**
 * @param string|int $stringOrInt
 * @param non-empty-string $nonEmptyString
 */
function foo(int $int, string $string, float $float, $stringOrInt, $numericString, $literalString, string $nonEmptyString) : void{
	assertType('int', ~$int);
	assertType('string', ~$string);
	assertType('non-empty-string', ~$nonEmptyString);
	assertType('int', ~$float);
	assertType('int|string', ~$stringOrInt);
	assertType("'" . (~"abc") . "'", ~"abc");
	assertType('int', ~1); //result is dependent on PHP_INT_SIZE
}
