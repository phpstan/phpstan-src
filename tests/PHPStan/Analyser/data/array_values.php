<?php

namespace ArrayValues;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function foo1($mixed): void
	{
		if(is_array($mixed)) {
			assertType('list<mixed>', array_values($mixed));
		} else {
			assertType('mixed~array', $mixed);
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
}
