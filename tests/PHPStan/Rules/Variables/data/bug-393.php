<?php

namespace Bug393;

class Foo
{
	private $privateProperty;
}

(\Closure::bind(
	function () {
		$this->privateProperty = 123;
	},
	new Foo(),
	Foo::class
))();

class Bar extends Foo
{
}

(\Closure::bind(
	function () {
		$this->privateProperty = 123;
	},
	new Bar(),
	Foo::class
))();
