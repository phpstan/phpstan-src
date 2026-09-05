<?php declare(strict_types = 1);

namespace Bug9685;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{
	protected function working(string $filter): int
	{
		$all = false;
		if ($filter === 'all') {
			$date = new \DateTime();
			$all = true;
		}

		if ($all) {
			assertVariableCertainty(TrinaryLogic::createYes(), $date);
			return (int) $date->format('Y');
		}

		return 0;
	}

	protected function test(string $filter): int
	{
		$all = false;
		if ($filter === 'all') {
			$date = new \DateTime();
			$all = true;

			if (mt_rand() === 0) { // all other code expect this condition is the same as in self::working()
				$all = false;
			}
		}

		if ($all) {
			assertVariableCertainty(TrinaryLogic::createYes(), $date);
			return (int) $date->format('Y');
		}

		return 0;
	}
}
