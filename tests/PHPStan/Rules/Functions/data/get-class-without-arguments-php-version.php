<?php

namespace GetClassWithoutArgumentsPhpVersion;

class Foo
{

	public function doFoo(): void
	{
		if (PHP_VERSION_ID < 80300) {
			echo get_class();
		}

		if (PHP_VERSION_ID >= 80300) {
			echo get_class();
		}
	}

}
