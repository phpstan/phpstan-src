<?php // lint >= 8.3

namespace ReadonlyClass;

readonly class Foo
{

}

class Bar
{

	public function doFoo(): void
	{
		$c = new readonly class () {

		};
	}

}
