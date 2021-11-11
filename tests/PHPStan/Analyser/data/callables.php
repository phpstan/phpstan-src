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
		$arrayWithStaticMethodActuallyStatic = ['Callables\\Foo', 'doBarActuallyStatic'];
		$stringWithStaticMethod = 'Callables\\Foo::doFoo';
		$stringWithStaticMethodActuallyStatic = 'Callables\\Foo::doFooActuallyStatic';
		$arrayWithInstanceMethod = [$this, 'doFoo'];
		die;
	}

	public static function doFooActuallyStatic(): float {

	}

	public function doBar(): Bar
	{

	}

	public static function doBarActuallyStatic(): Bar
	{

	}

	public function __invoke(): int
	{

	}

}
