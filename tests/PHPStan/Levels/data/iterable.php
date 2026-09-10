<?php

namespace Levels\Iterables;

class Foo
{

	/**
	 * @param mixed[] $array
	 * @param mixed[]|null $arrayOrNull
	 * @param int $int
	 * @param int|float $intOrFloat
	 * @param mixed[]|false $arrayOrFalse
	 */
	public function doFoo(
		array $array,
		?array $arrayOrNull,
		int $int,
		$intOrFloat,
		$arrayOrFalse
	)
	{
		foreach ($array as $val) {
			var_dump($val);
		}
		foreach ($arrayOrNull as $val) {
			var_dump($val);
		}
		foreach ($int as $val) {
			var_dump($val);
		}
		foreach ($intOrFloat as $val) {
			var_dump($val);
		}
		foreach ($arrayOrFalse as $val) {
			var_dump($val);
		}
	}

}
