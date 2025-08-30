<?php

namespace BugObject;

class B {
	/**
	 * @param array{baz: 21}|array{foo: 17, bar: 19} $array
	 * @param int|object $key
	 */
	public function foo($array, $key)
	{
		$array[$key];
	}

	/**
	 * @param array<string, int> $array
	 * @param object $key
	 */
	public function foo2($array, $key)
	{
		$array[$key];
	}
}
