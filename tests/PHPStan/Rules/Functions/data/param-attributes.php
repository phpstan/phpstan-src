<?php

namespace ParamAttributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Foo
{

}

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Bar
{

}

#[\Attribute(\Attribute::TARGET_ALL)]
class Baz
{

}

class Lorem
{

	public function doFoo(
		#[Foo]
		$foo
	)
	{

	}

}

class Ipsum
{

	public function doFoo(
		#[Bar]
		$foo
	)
	{

	}

}

class Dolor
{

	public function doFoo(
		#[Baz]
		$foo
	)
	{

	}

}
