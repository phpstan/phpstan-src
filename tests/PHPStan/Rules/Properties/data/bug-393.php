<?php

namespace Bug393;

class Foo
{
	private $privateProperty;
}

class Bar extends Foo
{
}

function test()
{
	$foo = new Foo();

	(\Closure::bind(
		static function () use ($foo) {
			$foo->privateProperty = 123;
		},
		null,
		Foo::class
	))();

	(\Closure::bind(
		static function () {
			$bar = new Bar();
			$bar->privateProperty = 123;
		},
		null,
		Foo::class
	))();
}
