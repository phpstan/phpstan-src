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
			assertType('0', $rowIndex); // `0`, because the IF in line 41 is always TRUE
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

class Test3
{
	/**
	 * @param int<0, 30> $columnIndex
	 */
	public function iterateRowColumnIndicesDecrementing(int $rows, int $columns, int $columnIndex): void
	{
		if ($rows < 1 || $columns < 1) return;
		$size = $rows * $columns;

		for ($i = 0; $i < $size; $i++) {
			assertType('int<min, 30>', $columnIndex);
			if ($columnIndex < 3) {
				$columnIndex--;
			} else {
				$columnIndex = 0;
			}
			assertType('int<min, 1>', $columnIndex);
		}
	}
}

class Test4
{
	/**
	 * @param int<0, 10> $index
	 */
	public function integerRangeGeneralization(int $rows, int $index): void
	{
		if ($rows < 1) return;

		for ($i = 0; $i < $rows; $i++) {
			assertType('int<-3, 13>', $index);
			if ($index > 5) {
				$index++;
			} else {
				$index--;
			}
		}
	}
}

class Test5
{
	/**
	 * @param int<5, 15> $index
	 */
	public function integerRangeGeneralizationBothDirections(int $rows, int $index): void
	{
		if ($rows < 1) return;

		for ($i = 0; $i < $rows; $i++) {
			assertType('int<-10, max>', $index);
			if ($index > 10) {
				$index++;
			} else {
				$index = -$index;
			}
		}
	}
}

class Bug12163
{
	/**
	 * @param non-negative-int $value
	 * @return void
	 */
	private function checkNonNegative(int $value): void
	{
		sleep(1);
	}

	public function iterateRowColumnIndices(int $rows, int $columns): void
	{
		if ($rows < 1 || $columns < 1) return;
		$size = $rows * $columns;

		$rowIndex = 0;
		$columnIndex = 0;
		for ($i = 0; $i < $size; $i++) {
			$this->checkNonNegative($rowIndex);
			$this->checkNonNegative($columnIndex);
			if ($columnIndex < $columns) {
				$columnIndex++;
			} else {
				$columnIndex = 0;
				$rowIndex++;
			}
		}
	}
}
