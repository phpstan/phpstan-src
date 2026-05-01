<?php

namespace Bug11101;

class Foo
{

	/**
	 * @param array<int> $array
	 */
	public function doFoo(array $array): void
	{
		array_filter($array, 'is_string');
		array_map('is_string', $array);
		array_reduce($array, function ($carry, $item) {
			return $carry + $item;
		}, 0);
	}

}
