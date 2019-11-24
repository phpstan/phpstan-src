<?php

namespace StubsIntegrationTest;

class Foo
{

	public function doFoo($i)
	{
		return '';
	}

}

function (Foo $foo) {
	$string = $foo->doFoo('test');
	$foo->doFoo($string);
};

class FooChild extends Foo
{

	public function doFoo($i)
	{
		return '';
	}

}

function (FooChild $fooChild) {
	$string = $fooChild->doFoo('test');
	$fooChild->doFoo($string);
};
