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
		$result = $int + $int;
		$result = $int + $intOrString;
		$result = $int + $stringOrObject;
		$result = $int + $string;
		$result = $string + $string;
		$result = $intOrString + $stringOrObject;
		$result = $intOrString + $string;
		$result = $stringOrObject + $stringOrObject;
	}

}
