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

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Qux
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

class Sit
{

	public function __construct(
		#[Foo]
		public $foo,
		#[Bar]
		public $bar,
		#[Qux]
		public $qux,
		#[Qux]
		protected $qux2,
		#[Qux]
		private $qux3,
		#[Qux]
		$qux4
	)
	{

	}

}
