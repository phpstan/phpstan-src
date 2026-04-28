<?php

namespace Bug1311ConstantArray;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @var array<int, string>
	 */
	private $list = [];

	public function convertListByRefWithoutKey(): void
	{
		$temp = [1, 2, 3];

		foreach ($temp as &$item) {
			$item = (string) $item;
		}

		assertType("array{'1'|'2'|'3', '1'|'2'|'3', '1'|'2'|'3'}", $temp);

		$this->list = $temp;
	}

	public function convertListByRefWithKey(): void
	{
		$temp = [1, 2, 3];

		foreach ($temp as $k => &$item) {
			$item = (string) $item;
		}

		assertType("array{'1'|'2'|'3', '1'|'2'|'3', '1'|'2'|'3'}", $temp);

		$this->list = $temp;
	}

	public function byRefConstantArrayConditional(): void
	{
		$temp = [1, 2, 3];

		foreach ($temp as &$item) {
			if (rand(0, 1)) {
				$item = (string) $item;
			}
		}

		assertType("array{1|2|3|'1'|'2'|'3', 1|2|3|'1'|'2'|'3', 1|2|3|'1'|'2'|'3'}", $temp);
	}

	public function byRefConstantArrayWithBreak(): void
	{
		$temp = [1, 2, 3];

		foreach ($temp as &$item) {
			$item = (string) $item;
			if (rand(0, 1)) {
				break;
			}
		}

		assertType('array{1, 2, 3}', $temp);
	}

	public function byRefConstantArrayIntval(): void
	{
		$temp = ['a', 'b', 'c'];

		foreach ($temp as &$item) {
			$item = strlen($item);
		}

		assertType('array{1, 1, 1}', $temp);
	}

	public function byRefConstantArrayStringKeys(): void
	{
		$temp = ['x' => 1, 'y' => 2];

		foreach ($temp as &$v) {
			$v = (string) $v;
		}

		assertType("array{x: '1'|'2', y: '1'|'2'}", $temp);
	}

	public function byRefConstantArrayNoOverwrite(): void
	{
		$temp = [1, 2, 3];

		foreach ($temp as &$item) {
			echo $item;
		}

		assertType('array{1, 2, 3}', $temp);
	}
}
