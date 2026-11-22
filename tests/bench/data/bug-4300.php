<?php declare(strict_types=1);

/*
 * instructions: 29.25M
 * cycles: 44.55M
 * memory: 258.2KiB
 */

namespace Bug4300;

class Widget
{

	public function getDefault(): int
	{
		$column1 = [];
		$column2 = [];

		$column = count($column1) > count($column2) ? 2 : 1;

		return $column;
	}

}
