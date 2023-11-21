<?php // lint >= 8.3

namespace MissingReadonlyAnonymousClassPropertyAssign;

class Foo
{

	public function doFoo(): void
	{
		$c = new readonly class () {
			public int $foo;
		};
	}

}
