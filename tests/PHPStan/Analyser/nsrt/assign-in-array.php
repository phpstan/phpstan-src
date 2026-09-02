<?php

namespace AssignInArray;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		$a = [
			$b = 1,
			$b + 1,
			$c = $b,
			$c + 2,
			$c++,
			$c,
		];
		assertType('array{1, 2, 1, 3, 1, 2}', $a);
	}

}
