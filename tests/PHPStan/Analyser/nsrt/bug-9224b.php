<?php // lint >= 8.1

namespace Bug9224b;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @param array<int> $arr */
	public function sayHello(array $arr): void
	{
		assertType('array<9.223372036854776E+18|int<0, max>>', array_map('abs', $arr));
		assertType('array<9.223372036854776E+18|int<0, max>>', array_map(abs(...), $arr));
	}

}
