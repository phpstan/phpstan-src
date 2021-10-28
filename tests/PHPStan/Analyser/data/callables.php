<?php

namespace Callables;

class Foo
{

	public function doFoo(): float
	{
		$closure = function (): string {

		};
		$foo = $this;
		$arrayWithStaticMethod = ['Callables\\Foo', 'doBar'];
		$stringWithStaticMethod = 'Callables\\Foo::doBaz';
		$arrayWithInstanceMethod = [$this, 'doFoo'];
		die;
	}

	public static function doBar(): Bar
	{

	}

	public static function doBaz(): float
	{

	}

	public function __invoke(): int
	{

	}

}
