<?php // lint >= 8.1

namespace Bug9224b;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @param array<int> $arr */
	public function sayHello(array $arr): void
	{
		assertType('array<int<0, max>>', array_map('abs', $arr));
		assertType('array<int<0, max>>', array_map(abs(...), $arr));
	}

}
