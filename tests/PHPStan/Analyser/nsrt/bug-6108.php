<?php

namespace Bug6108;

use function PHPStan\Testing\assertType;

class Foo
{


	/**
	 * @return array{
	 *	a: int[],
	 * 	b: int[],
	 *	c: bool
	 * }
	 */
	function doFoo(): array {
		return [
			'a' => [1, 2],
			'b' => [3, 4, 5],
			'c' => true,
		];
	}

	function doBar()
	{
		$x = $this->doFoo();
		$test = ['a' => true, 'b' => false];
		foreach ($test as $key => $value) {
			if ($value) {
				assertType('\'a\'|\'b\'', $key); // could be just 'a'
				assertType('array<int>', $x[$key]);
			}
		}
	}

}
