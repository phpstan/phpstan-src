<?php declare(strict_types = 1);

namespace Bug12163;

use function PHPStan\Testing\assertType;

class Test
{
	public function iterateRowColumnIndicesIncrementing(int $rows, int $columns): void
	{
		if ($rows < 1 || $columns < 1) return;
		$size = $rows * $columns;

		$rowIndex = 0;
		$columnIndex = 0;
		for ($i = 0; $i < $size; $i++) {
			assertType('int<0, max>', $rowIndex);
			assertType('int<0, max>', $columnIndex);
			if ($columnIndex < $columns) {
				$columnIndex++;
			} else {
				$columnIndex = 0;
				$rowIndex++;
			}
		}
	}
}

class Test2
{
	public function iterateRowColumnIndicesDecrementing(int $rows, int $columns): void
	{
		if ($rows < 1 || $columns < 1) return;
		$size = $rows * $columns;

		$rowIndex = 0;
		$columnIndex = 0;
		for ($i = 0; $i < $size; $i++) {
			assertType('0', $rowIndex);
			assertType('int<min, 0>', $columnIndex);
			if ($columnIndex < $columns) {
				$columnIndex--;
			} else {
				$columnIndex = 0;
				$rowIndex++;
			}
		}
	}
}
