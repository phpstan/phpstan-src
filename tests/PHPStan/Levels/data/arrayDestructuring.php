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
		var_dump($a, $b, $c);
		[$a, $b, $c] = $arrayOrNull;
		var_dump($a, $b, $c);
	}

	/**
	 * @param iterable<int, string> $it
	 */
	public function doBar(iterable $it): void
	{
		[$a] = $it;
		var_dump($a);
	}

	public function doBaz(): void
	{
		$array = ['a', 'b', 'c'];
		[$a] = $array;
		var_dump($a);
		[$a, , , $d] = $array;
		var_dump($a, $d);
	}

}
