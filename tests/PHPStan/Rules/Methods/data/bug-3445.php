<?php

namespace Bug3445;

class Foo
{

	public function doFoo(self $test): void
	{

	}

	public function doBar($test = UnknownClass::BAR): void
	{

	}

}

class Bar
{

	public function doFoo(Foo $foo)
	{
		$foo->doFoo(new Foo());
		$foo->doFoo($this);

		$foo->doBar(new \stdClass());
	}

}
