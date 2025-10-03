<?php

namespace Bug7143;

class Foo
{
	/**
	 * @param array{foo?: string, bar?: string}&non-empty-array $arr
	 */
	public function test(array $arr): void
	{
		echo $arr['foo'];
		echo $arr['bar'];
	}

	/**
	 * @param array{foo?: string, bar?: string, 1?:1, 2?:2, 3?:3, 4?:4, 5?:5, 6?:6, 7?:7, 8?:8, 9?:9}&non-empty-array $arr
	 */
	public function test2(array $arr): void
	{
		echo $arr['foo'];
		echo $arr['bar'];
	}
}
