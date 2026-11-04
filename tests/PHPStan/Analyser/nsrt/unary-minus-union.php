<?php declare(strict_types = 1);

namespace UnaryMinusUnion;

use function PHPStan\Testing\assertType;

/**
 * @param int<1, 5>|1.5 $rangeAndConstant
 * @param int<1, 5>|int<10, 20> $ranges
 * @param int<1, 5>|'7' $rangeAndNumericString
 * @param 1|2 $constants
 */
function negate($rangeAndConstant, $ranges, $rangeAndNumericString, $constants): void
{
	assertType('-1.5|int<-5, -1>', -$rangeAndConstant);
	assertType('int<-20, -10>|int<-5, -1>', -$ranges);
	assertType('-7|int<-5, -1>', -$rangeAndNumericString);
	assertType('-2|-1', -$constants);
}
