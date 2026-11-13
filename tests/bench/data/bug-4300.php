<?php declare(strict_types=1);

/*
 * instructions: 26.99M
 * cycles: 41.33M
 * memory: 36.00MiB
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
