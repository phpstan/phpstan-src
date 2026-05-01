<?php // lint >= 8.4

namespace Bug11101Php84;

class Foo
{

	/**
	 * @param array<int> $array
	 */
	public function doFoo(array $array): void
	{
		array_find($array, fn ($v) => $v > 5);
		array_find_key($array, fn ($v) => $v > 5);
		array_any($array, fn ($v) => $v > 5);
		array_all($array, fn ($v) => $v > 5);
	}

}
