<?php

namespace Levels\ArrayDestructuring;

class Foo
{

	/**
	 * @param mixed[] $array
	 * @param mixed[]|null $arrayOrNull
	 */
	public function doFoo(array $array, ?array $arrayOrNull): void
	{
		[$a, $b, $c] = $array;
		[$a, $b, $c] = $arrayOrNull;
	}

	/**
	 * @param iterable<int, string> $it
	 */
	public function doBar(iterable $it): void
	{
		[$a] = $it;
	}

	public function doBaz(): void
	{
		$array = ['a', 'b', 'c'];
		[$a] = $array;
		[$a, , , $d] = $array;
	}

}
