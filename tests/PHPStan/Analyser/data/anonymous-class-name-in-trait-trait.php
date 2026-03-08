<?php

namespace AnonymousClassNameInTrait;

use function PHPStan\Testing\assertType;

trait FooTrait
{

	public function doFoo()
	{
		new class () {

			public function doFoo()
			{
				assertType('$this(AnonymousClass74dc65f5cc25b18fc1899c49ad61311b)', $this);
			}
		};
	}

}
