<?php

namespace Bug8075;

class Foo
{

	public function doFoo(): void
	{
		$arr = [['a' => 0]];

		['b' => $val] = $arr[0]; // error - works

		foreach ($arr as ['b' => $valueB]) { // error - should be reported
		}

		foreach ($arr as ['b' => $valueB, 'a' => $valueA]) { // error on 'b'
		}

		foreach ($arr as ['a' => $valueA]) { // no error - 'a' exists
		}

		foreach ($arr as $item) {
			['b' => $valueB] = $item; // error - works
		}
	}

	/**
	 * @param array<int, array{name: string, age: int}> $people
	 */
	public function doBar(array $people): void
	{
		foreach ($people as ['name' => $name, 'age' => $age]) { // no error
		}

		foreach ($people as ['name' => $name, 'missing' => $missing]) { // error on 'missing'
		}
	}

	/**
	 * @param list<array{0: string, 1: int}> $tuples
	 */
	public function doBaz(array $tuples): void
	{
		foreach ($tuples as [$first, $second]) { // no error
		}

		foreach ($tuples as [$first, $second, $third]) { // error on offset 2
		}
	}

	/**
	 * @param list<array{a: array{x: int, y: int}}> $nested
	 */
	public function doNested(array $nested): void
	{
		foreach ($nested as ['a' => ['x' => $x, 'y' => $y]]) { // no error
		}

		foreach ($nested as ['a' => ['x' => $x, 'z' => $z]]) { // error on 'z'
		}
	}

}
