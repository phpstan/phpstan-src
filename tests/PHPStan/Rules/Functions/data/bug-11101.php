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

	/**
	 * @param array<int> $array
	 */
	public function doBar(array $array, callable $callback): void
	{
		// These should NOT be reported because the callback might be impure
		array_filter($array, $callback);
		array_map($callback, $array);
		array_reduce($array, $callback, 0);

		// Impure closure should not be reported
		array_filter($array, function ($v) {
			echo $v;
			return $v > 0;
		});
	}

}
