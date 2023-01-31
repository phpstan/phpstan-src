<?php

namespace Bug393;

class Foo
{
	private $privateProperty;
}

$foo = new Foo();

(\Closure::bind(
	static function () use ($foo) {
		$foo->privateProperty = 123;
	},
	null,
	Foo::class
))();

class Bar extends Foo
{
}

(\Closure::bind(
	static function () {
		$bar = new Bar();
		$bar->privateProperty = 123;
	},
	null,
	Foo::class
))();
