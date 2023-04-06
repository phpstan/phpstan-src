<?php

namespace PropertiesObjectShapes;

class Foo
{

	/**
	 * @param object{foo: int, bar?: string} $o
	 * @return void
	 */
	public function doFoo(object $o): void
	{
		echo $o->foo;
		echo $o->bar;
		echo $o->baz;

		$o->foo = 1;
		$o->bar = 2;
		$o->baz = 3;
	}

}
