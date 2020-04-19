<?php

namespace CaughtExceptionClassExists;

class Foo
{

	public function doFoo(): void
	{
		if (!class_exists(FooException::class)) {
			return;
		}

		try {

		} catch (FooException $e) {

		}
	}

}
