<?php

namespace InstanceofClassExists;

class Foo
{

	public function doFoo(): void
	{
		/** @var object $object */
		$object = doFoo();
		class_exists(Bar::class) ? $object instanceof Bar : false;
	}

}
