<?php

namespace Bug3858;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class BinarySearch
{
	/**
	 * @param mixed[] $items
	 */
	public function __invoke(array $items): void
	{
		for ($start = 0, $end = count($items);
			 $i = $start + (int) (($end - $start) / 2), $i < $end;
		) {
			// do something here, then break at some point
			break;
		}

		// $i is always defined here, even with an empty array
		assertVariableCertainty(TrinaryLogic::createYes(), $i);
	}
}
