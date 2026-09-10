<?php

namespace Levels\Casts;

class Foo
{

	/**
	 * @param mixed[] $array
	 * @param mixed[]|(callable(): mixed) $arrayOrCallable
	 * @param mixed[]|float|int $arrayOrFloatOrInt
	 */
	public function doFoo(
		array $array,
		$arrayOrCallable,
		$arrayOrFloatOrInt
	)
	{
		$tests = [];
		$tests[] = (int) $array;
		$tests[] = (int) $arrayOrCallable;
		$tests[] = (string) $arrayOrFloatOrInt;
		var_dump($tests);
	}

}
