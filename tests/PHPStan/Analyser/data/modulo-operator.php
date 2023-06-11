<?php

namespace ModuloOperator;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param positive-int $p
	 * @param int<5, 10> $range
	 * @param int<0, max> $zeroOrMore
	 * @param 1|2|3 $intConst
	 * @param int<min, 3>|int<4, max> $unionRange
	 * @param int<min, 3>|7 $hybridUnionRange
	 */
	function doBar(int $i, int $j, $p, $range, $zeroOrMore, $intConst, $unionRange, $hybridUnionRange, $mixed)
	{
		assertType('int<-1, 1>', $i % 2);
		assertType('int<0, 1>', $p % 2);

		assertType('int<-2, 2>', $i % 3);
		assertType('int<0, 2>', $p % 3);

		assertType('0|1|2', $intConst % 3);
		assertType('int<-2, 2>', $i % $intConst);
		assertType('int<0, 2>', $p % $intConst);

		assertType('int<0, 2>', $range % 3);

		assertType('int<-9, 9>', $i % $range);
		assertType('int<0, 9>', $p % $range);

		assertType('int', $i % $unionRange);
		assertType('int<0, max>', $p % $unionRange);

		assertType('int<-6, 6>', $i % $hybridUnionRange);
		assertType('int<0, 6>', $p % $hybridUnionRange);

		assertType('int<0, max>', $zeroOrMore % $mixed);

		if ($i === 0) {
			return;
		}

		assertType('int', $j % $i);
	}

	function moduleOne(int $i, float $f) {
		assertType('0', true % '1');
		assertType('0', false % '1');
		assertType('0', null % '1');
		assertType('0', -1 % '1');
		assertType('0', 0 % '1');
		assertType('0', 1 % '1');
		assertType('0', '1' % '1');
		assertType('*ERROR*', 1.24 % '1');

		assertType('0', $i % 1.0);
		assertType('0', $f % 1.0);

		assertType('0', $i % '1.0');
		assertType('0', $f % '1.0');

		assertType('0', $i % '1');
		assertType('0', $f % '1');

		assertType('0', $i % true);
		assertType('0', $f % true);

		$i %= '1';
		$f %= '1';
		assertType('0', $i);
		assertType('0', $f);
	}
}
