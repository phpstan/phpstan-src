<?php // lint >= 8.4

namespace PropertyHookAttributes;

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

	public int $i {
		#[Foo]
		get {

		}
	}

}

class Ipsum
{

	public int $i {
		#[Bar]
		get {

		}
	}

}

class Dolor
{

	public int $i {
		#[Baz]
		get {

		}
	}

}
