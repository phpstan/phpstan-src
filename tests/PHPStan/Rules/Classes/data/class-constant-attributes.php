<?php

namespace ClassConstantAttributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Foo
{

}

#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
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
	private const FOO = 1;

}

class Ipsum
{

	#[Bar]
	private const FOO = 1;

}

class Dolor
{

	#[Baz]
	private const FOO = 1;

}
