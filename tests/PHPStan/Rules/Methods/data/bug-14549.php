<?php

namespace Bug14549;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param callable-array $task
	 */
	public function doFoo(array $task): void
	{
		foreach($task as $k => $v) {
			assertType('0|1', $k);
			assertType('object|non-falsy-string', $v);
		}
		assertType('class-string|object', $task[0]);
		assertType('non-falsy-string', $task[1]);
	}

	/**
	 * @param non-empty-list<string> $list
	 */
	public function doBar(array $list): void
	{
		if ($list[0] !== '') {
			assertType('non-empty-list<string>&hasOffsetValue(0, non-empty-string)', $list);

			if (is_callable($list)) {
				assertType('non-empty-list<string>&callable(): mixed&hasOffsetValue(0, non-empty-string)', $list);
				assertType('non-empty-string', $list[0]);
				assertType('non-falsy-string', $list[1]);

				foreach($list as $k => $v) {
					assertType('0|1', $k);
					assertType('non-falsy-string', $v);
				}
			}
		}
	}

	/**
	 * @param (array&callable(array): array) $array
	 */
	public function doIntersection($array): void
	{
	}

	/**
	 * @param callable&array $task
	 */
	public function doBaz(array $task): void
	{
	}

}


