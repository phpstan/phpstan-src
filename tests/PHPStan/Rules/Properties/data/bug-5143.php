<?php

namespace Bug5143;

trait Foo
{
	public static $property = 0;
}

class Bar
{

	public function doFoo(): void
	{
		echo Foo::$property;
	}

}
