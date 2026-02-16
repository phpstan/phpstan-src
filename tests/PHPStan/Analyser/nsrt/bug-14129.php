<?php

namespace Bug14129;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{foo: int}  $a
	 */
	public function doFoo(array $a): void
	{
		$k = rand(0, 1) ? 'a' : 'b';
		$a[$k] = 256;
		assertType('array{foo: int, a: 256}|array{foo: int, b: 256}', $a);
	}

	/**
	 * @param array{foo: int}  $a
	 * @param int<1,5> $intRange
	 */
	public function doBar(array $a, $intRange): void
	{
		$a[$intRange] = 256;
		assertType('array{foo: int, 1: 256}|array{foo: int, 2: 256}|array{foo: int, 3: 256}|array{foo: int, 4: 256}|array{foo: int, 5: 256}', $a);
	}

}
