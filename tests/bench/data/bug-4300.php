<?php declare(strict_types=1);

/*
 * instructions: 29.01M
 * cycles: 44.34M
 * memory: 322.1KiB
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
