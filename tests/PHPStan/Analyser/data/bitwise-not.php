<?php

namespace BitwiseNot;

use function PHPStan\Testing\assertType;

/**
 * @param string|int $stringOrInt
 * @param non-empty-string $nonEmptyString
 * @param non-falsy-string $nonFalsyString
 */
function foo(int $int, string $string, float $float, $stringOrInt, string $nonEmptyString, $nonFalsyString) : void{
	assertType('int', ~$int);
	assertType('string', ~$string);
	assertType('non-empty-string', ~$nonEmptyString);
	assertType('non-empty-string', ~$nonFalsyString); // ~"\xcf" results in '0'
	assertType('int', ~$float);
	assertType('int|string', ~$stringOrInt);
	assertType("'" . (~"abc") . "'", ~"abc");
	assertType('int', ~1); //result is dependent on PHP_INT_SIZE
}
