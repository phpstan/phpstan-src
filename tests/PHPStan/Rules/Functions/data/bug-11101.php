<?php declare(strict_types = 1);

namespace Bug11101;

class HelloWorld
{
	/** @param array<int> $array */
	public function sayHello(array $array): void
	{
		// Pure callbacks - should report "no effect"
		array_filter($array, 'is_string');
		array_map('is_string', $array);
		array_reduce($array, function ($carry, $item) {
			return $carry + $item;
		}, 0);

		// Impure callbacks - should NOT report "no effect"
		array_filter($array, function ($item) {
			echo $item;
			return true;
		});
		array_map(function ($item) {
			echo $item;
			return $item;
		}, $array);
		array_reduce($array, function ($carry, $item) {
			echo $item;
			return $carry + $item;
		}, 0);
	}
}
