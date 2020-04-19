<?php

namespace InstantiationClassExists;

class Foo
{

	public function doFoo(): void
	{
		if (class_exists(Bar::class)) {
			$bar = new Bar();
		}
	}

}
