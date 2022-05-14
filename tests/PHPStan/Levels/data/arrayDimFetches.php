<?php

namespace Levels\ArrayDimFetches;

class Foo
{

	/**
	 * @param \stdClass $stdClass
	 * @param mixed[]|null $arrayOrNull
	 */
	public function doFoo(\stdClass $stdClass, ?array $arrayOrNull)
	{
		echo $stdClass[1];
		echo $arrayOrNull[0];

		$arr = [
			'a' => 1,
		];

		echo $arr['b'];

		if (rand(0, 1)) {
			$arr = $stdClass;
		}

		echo $arr['a'];
		echo $arr['b'];
	}

	public function doBar()
	{
		$arr = [
			'a' => 1,
		];
		if (rand(0, 1)) {
			$arr['b'] = 1;
		}

		echo $arr['b'];
	}

	/**
	 * @param array<int, string>|false $a
	 * @param array<int, string>|null $b
	 */
	public function doBaz($a, $b): void
	{
		echo $a[0];
		echo $b[0];
	}

	/**
	 * @param iterable<int|string, object> $iterable
	 */
	public function iterableOffset($iterable): void
	{
		var_dump($iterable['foo']);
	}

}
