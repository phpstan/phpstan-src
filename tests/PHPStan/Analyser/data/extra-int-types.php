<?php

namespace ExtraIntTypes;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param positive-int $positiveInt
	 * @param negative-int $negativeInt
	 */
	public function doFoo(
		int $positiveInt,
		int $negativeInt,
		string $str
	): void
	{
		assertType('int<1, max>', $positiveInt);
		assertType('int<min, -1>', $negativeInt);
		assertType('false', strpos('u', $str) === -1);
		assertType('true', strpos('u', $str) !== -1);
	}

}
