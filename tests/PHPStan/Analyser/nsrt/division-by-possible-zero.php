<?php // lint >= 8.0

namespace DivisionByPossibleZero;

use function PHPStan\Testing\assertType;

/**
 * A zero divisor throws since PHP 8.0, so it adds nothing to the quotient. Before
 * PHP 8.0 it gives INF or NAN, and the float part of these quotients is float.
 *
 * @param int<0, max> $range1
 * @param int<min, 0> $range2
 * @param int<1, 10> $r1
 * @param int<min, 5> $rMin
 * @param int<0, max> $a
 * @param int<0, max> $b
 * @param int<-1, 2> $p
 * @param int<-1, 2> $u
 */
function doFoo(int $range1, int $range2, int $int, int $r1, int $i, int $rMin, int $a, int $b, int $p, int $u): void
{
	assertType('float<0.0, 5.0>|int<1, 5>', 5 / $range1);
	assertType('float<-5.0, 0.0>|int<-5, -1>', 5 / $range2);
	assertType('float<min, 0.0>|int<min, 0>', $range1 / $range2);
	assertType('(float<-5.0, 5.0>|int<-5, -1>|int<1, 5>)', 5 / $int);
	assertType('(float<-10.0, 10.0>|int<-10, -1>|int<1, 10>)', $r1 / $i);
	assertType('float<-10.0, 0.0>|float<0.2, 10.0>|int<-10, -1>|int<1, 10>', $r1 / $rMin);
	assertType('float<0.0, max>|int<0, max>', $a / $b);
	assertType('-1|1|float<-1.0, -0.2>|float<0.0, 1.0>', -1 / $rMin);
	assertType('float<-2.0, 2.0>|int<-2, 2>', $p / $u);
}
