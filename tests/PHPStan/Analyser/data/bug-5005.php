<?php

namespace Bug5005;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(
		int|false $int1,
		int|false $int2,
		int|null $int3,
		int|null $int4,
		int $int5,
		int $int6,
	): void
	{
		assertType('(float|int)', $int1 / $int2);
		assertType('(float|int)', $int3 / $int4);
		assertType('(float|int)', $int5 / $int6);
	}

}
