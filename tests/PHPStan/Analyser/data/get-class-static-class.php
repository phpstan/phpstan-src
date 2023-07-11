<?php

namespace GetClassStaticClass;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(object $o): void
	{
		if (get_class($o) !== static::class) {
			return;
		}

		assertType('static(GetClassStaticClass\Foo)', $o);
	}

	public function doFoo2(object $o): void
	{
		if (static::class !== get_class($o)) {
			return;
		}

		assertType('static(GetClassStaticClass\Foo)', $o);
	}

}
