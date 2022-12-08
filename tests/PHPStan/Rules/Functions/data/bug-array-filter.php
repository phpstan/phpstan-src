<?php

namespace Bug2065;

class Test
{
	/**
	 * @param class-string[] $array
	 */
	public function foo(array $array): array
	{
		return array_filter($array);
	}
}
