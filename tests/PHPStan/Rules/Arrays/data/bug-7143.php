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
}
