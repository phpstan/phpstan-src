<?php

namespace MethodImplicitNullable;

use stdClass;

class Foo
{

	public function doFoo(
		$a = null,
		int $b = 1,
		int $c = null,
		mixed $d = null,
		int|string $e = null,
		int|string|null $f = null,
		stdClass $g = null,
		?stdClass $h = null,
	): void
	{
	}

}
