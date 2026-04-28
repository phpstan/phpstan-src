<?php

namespace Bug1311;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @var array<int, string>
	 */
	private $list = [];

	/**
	 * @param array<int, int> $temp
	 */
	public function convertList(array $temp): void
	{
		foreach ($temp as &$item) {
			$item = (string) $item;
		}

		assertType('array<int, lowercase-string&numeric-string&uppercase-string>', $temp);

		$this->list = $temp;
	}

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

	public function constantArrayByRefSubElement(): void
	{
		$a = [
			[
				'one' => 'one',
				'two' => 'two',
			],
			[
				'one' => 'one',
			],
		];

		foreach ($a as &$testArray) {
			$testArray['two'] = 'two';
		}
		unset($testArray);

		assertType("array{array{one: 'one', two: 'two'}, array{one: 'one', two: 'two'}}", $a);

		$key = 'three';

		foreach ($a as $offset => $testArray) {
			$a[$offset][$key] = $key;
		}

		assertType("array{array{one: 'one', two: 'two', three: 'three'}, array{one: 'one', two: 'two', three: 'three'}}", $a);

		foreach ($a as $testArray) {
			assertType("array{one: 'one', two: 'two', three: 'three'}", $testArray);
			$testArray['two'];
			$testArray['three'];
		}
	}
}
