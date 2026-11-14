<?php declare(strict_types = 1);

/*
 * instructions: 46.27M
 * cycles: 68.48M
 * memory: 723.9KiB
 */

namespace Bug12787;

class HelloWorld
{
	protected const MAX_COUNT = 100000000;

	/**
	 * @return string[]
	 */
	public function accumulate(): array
	{
		$items = [];

		do {
			$items[] = 'something';
		} while (count($items) < self::MAX_COUNT);

		return $items;
	}
}
