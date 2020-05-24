<?php

namespace GlobalVariables;

class Foo
{

	public function doFoo()
	{
		global $foo, $bar;

		echo $foo;
		echo $bar;
	}

}
