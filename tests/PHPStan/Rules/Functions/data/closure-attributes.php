<?php

namespace ClosureAttributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Foo
{

}

#[\Attribute(\Attribute::TARGET_FUNCTION)]
class Bar
{

}

#[\Attribute(\Attribute::TARGET_ALL)]
class Baz
{

}

class Lorem
{

	public function doFoo()
	{
		#[Foo] function (): void {};
		#[Bar] function (): void {};
		#[Baz] function (): void {};
	}

}
