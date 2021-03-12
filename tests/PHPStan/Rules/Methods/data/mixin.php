<?php

namespace MixinMethods;

class Foo
{

	public function doFoo()
	{

	}

}

/**
 * @mixin Foo
 */
class Bar
{

	public function doBar()
	{

	}

}

function (Bar $bar): void {
	$bar->doFoo();
	$bar->doFoo(1);
};

class Baz extends Bar
{

}

function (Baz $baz): void {
	$baz->doFoo();
	$baz->doFoo(1);
};

/**
 * @template T of object
 * @mixin T
 */
class GenericFoo
{

}

class Test
{

	/**
	 * @param GenericFoo<\Exception> $foo
	 */
	public function doFoo(GenericFoo $foo): void
	{
		echo $foo->getMessage();
		echo $foo->getMessage(1);
		echo $foo->getMessagee();
	}

}
