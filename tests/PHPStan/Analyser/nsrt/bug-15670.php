<?php

namespace Bug15670;

use function PHPStan\Testing\assertType;

/**
 * @param 0|0.01|0.02|0.03|0.04|0.05|0.06|0.07|0.08|0.09|0.10|0.11 $a
 * @param 0.1|0.2|0.3|0.4|0.5|0.6|0.7|0.8|0.9|1.0|1.1 $b
 * @param 0|1|2|3|4|5|6|7|8|9|10|0.5 $c
 * @param 1|2|3|4|5|6|7|8|9|10|11 $d
 */
function mixedIntFloat($a, $b, $c, $d): void
{
	assertType('float', $a + $b);
	assertType('float|int', $c + $d);
	assertType('float|int', $c * $d);
}

final class Foo
{

	private const WEIGHTS = [1 => 0.0761, 2 => 0.0720, 3 => 0.0811, 4 => 0.0812, 5 => 0.0813, 6 => 0.0814, 7 => 0.0815, 8 => 0.0816, 9 => 0.0817, 10 => 0.0818, 11 => 0.0819, 12 => 0.1290];

	/** @param list<int<0, 12>> $months */
	public function doFoo(array $months): void
	{
		$weight = 0;
		foreach ($months as $month) {
			if ($month === 0) {
				break;
			}
			$weight += self::WEIGHTS[$month] ?? 0.0;
		}
		assertType('0|float', $weight);
	}

}
