<?php

namespace MixinProperties;

class Foo
{

	public $fooProp;

}

/**
 * @mixin Foo
 */
class Bar
{

}

function (Bar $bar): void {
	$bar->fooProp;
};

class Baz extends Bar
{

}

function (Baz $baz): void {
	$baz->fooProp;
};

/**
 * @template T
 * @mixin T
 */
class GenericFoo
{

}

class Test
{

	/**
	 * @param GenericFoo<\ReflectionClass> $foo
	 */
	public function doFoo(GenericFoo $foo): void
	{
		echo $foo->name;
		echo $foo->namee;
	}

}
