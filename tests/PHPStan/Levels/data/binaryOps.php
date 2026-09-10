<?php

namespace Levels\BinaryOps;

class Foo
{

	/**
	 * @param int $int
	 * @param string $string
	 * @param int|string $intOrString
	 * @param string|object $stringOrObject
	 */
	public function doFoo(
		int $int,
		string $string,
		$intOrString,
		$stringOrObject
	)
	{
		$results = [];
		$results[] = $int + $int;
		$results[] = $int + $intOrString;
		$results[] = $int + $stringOrObject;
		$results[] = $int + $string;
		$results[] = $string + $string;
		$results[] = $intOrString + $stringOrObject;
		$results[] = $intOrString + $string;
		$results[] = $stringOrObject + $stringOrObject;
		var_dump($results);
	}

}
