<?php

namespace OverriddenMethodPrototype;

class Foo
{

	protected function foo()
	{

	}

}

class Bar extends Foo
{

	public function foo()
	{

	}

}

function () {
	$bar = new Bar();
	$bar->foo();
};
