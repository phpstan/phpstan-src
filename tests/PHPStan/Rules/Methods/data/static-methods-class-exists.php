<?php

namespace StaticMethodsClassExists;

use function class_exists;

class Foo
{

	public function doFoo(): void
	{
		if (!class_exists(Bar::class)) {
			return;
		}

		Bar::doBar();
	}

}
