<?php declare(strict_types=1);

namespace Bug10650;

use function PHPStan\Testing\assertType;

class DistFoo
{
	/**
	 * @param list<'y'> $distPoints
	 */
	public function repro(array $distPoints): void
	{
		$ranges = [];
		$pointPrev = null;
		foreach ($distPoints as $distPoint) {
			if ($pointPrev !== null) {
				$ranges[] = 'x';
			}
			$pointPrev = $distPoint;
		}

		assertType('list<\'x\'>', $ranges);

		foreach (array_keys($ranges) as $key) {
			if (mt_rand() === 0) {
				unset($ranges[$key]);
			}
		}

		assertType('array<int<0, max>, \'x\'>', $ranges);
	}
}
