<?php

namespace StaticPropertiesClassExists;

class Foo
{

	public function doFoo(): void
	{
		if (!class_exists(Bar::class)) {
			return;
		}

		echo Bar::$foo;
	}

}
