<?php // lint >= 8.0

namespace ArrayValues;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function foo1($mixed): void
	{
		if(is_array($mixed)) {
			assertType('list<mixed>', array_values($mixed));
		} else {
			assertType('mixed~array<mixed, mixed>', $mixed);
			assertType('*NEVER*', array_values($mixed));
		}
	}

	/**
	 * @param list<string> $list
	 */
	public function foo2($list): void
	{
		if(is_array($list)) {
			assertType('list<string>', array_values($list));
		} else {
			assertType('*NEVER*', $list);
			assertType('*NEVER*', array_values($list));
		}
	}

	public function constantArrayType(): void
	{
		$numbers = array_filter(
			[1 => 'a', 2 => 'b', 3 => 'c'],
			static fn ($value) => mt_rand(0, 1) === 0,
		);
		assertType("array{0?: 'a'|'b'|'c', 1?: 'b'|'c', 2?: 'c'}", array_values($numbers));
	}

	/**
	 * @param array<string, non-empty-array<string, int>> $a
	 */
	public function arrayMap(array $a): void
	{
		assertType('array<string, non-empty-list<int>>', array_map(array_values(...), $a));
	}
}
