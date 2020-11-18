<?php

namespace MethodAttributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Foo
{

}

#[\Attribute(\Attribute::TARGET_METHOD)]
class Bar
{

}

#[\Attribute(\Attribute::TARGET_ALL)]
class Baz
{

}

class Lorem
{

	#[Foo]
	private function doFoo(): void
	{

	}

}

class Ipsum
{

	#[Bar]
	private function doFoo(): void
	{

	}

}

class Dolor
{

	#[Baz]
	private function doFoo(): void
	{

	}

}
