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
		$arrayWithStaticMethodActual = ['Callables\\Foo', 'doBarActual'];
		$stringWithStaticMethod = 'Callables\\Foo::doFoo';
		$stringWithStaticMethodActual = 'Callables\\Foo::doFooActual';
		$arrayWithInstanceMethod = [$this, 'doFoo'];
		die;
	}

	public static function doFooActual(): float {

	}

	public function doBar(): Bar
	{

	}

	public static function doBarActual(): Bar
	{

	}

	public function __invoke(): int
	{

	}

}
