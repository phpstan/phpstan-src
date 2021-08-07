<?php

namespace ModuloOperator;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param positive-int $p
	 * @param int<5, 10> $range
	 * @param int<0, max> $zeroOrMore
	 */
	function doBar(int $i, $p, $range, $zeroOrMore, $mixed)
	{
		assertType('int<-1, 1>', $i % 2);
		assertType('int<0, 1>', $p % 2);

		assertType('int<-2, 2>', $i % 3);
		assertType('int<0, 2>', $p % 3);

		assertType('int<0, 2>', $range % 3);

		assertType('int<-9, 9>', $i % $range);
		assertType('int<0, 9>', $p % $range);

		assertType('int<0, max>', $zeroOrMore % $mixed);
	}
}
