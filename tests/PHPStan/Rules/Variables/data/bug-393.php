<?php

namespace Bug393\Variables;

class Foo
{
	private $privateProperty;
}

class Bar extends Foo
{
}

function test()
{
	(\Closure::bind(
		function () {
			$this->privateProperty = 123;
		},
		new Foo(),
		Foo::class
	))();

	(\Closure::bind(
		function () {
			$this->privateProperty = 123;
		},
		new Bar(),
		Foo::class
	))();
}
