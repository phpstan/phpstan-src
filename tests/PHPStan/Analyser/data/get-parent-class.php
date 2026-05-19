<?php

namespace ParentClass;

class Foo
{

	public function doFoo()
	{
		'inParentClass';
	}

}

class Bar extends Foo
{

	use FooTrait;

	public function doBar()
	{
		'inChildClass';
	}

}

function (string $s) {
	die;
};

trait FooTrait
{

	public function doBaz()
	{
		'inTrait';
	}

}
