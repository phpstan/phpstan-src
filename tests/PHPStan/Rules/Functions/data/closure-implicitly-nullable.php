<?php // lint >= 8.0

namespace ClosureImplicitNullable;

class Foo
{

	public function doFoo(): void
	{
		$c = function (
			$a = null,
			int $b = 1,
			int $c = null,
			mixed $d = null,
			int|string $e = null,
			int|string|null $f = null,
			\stdClass $g = null,
			?\stdClass $h = null,
		): void {

		};
	}

}
