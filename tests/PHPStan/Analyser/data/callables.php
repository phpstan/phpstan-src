<?php

namespace Callables;

class Foo
{

	public function doFoo(): float
	{
		$closure = function (): string {

		};
		$foo = $this;
		$arrayWithStringLiteralAndStaticMethod = ['Callables\\Foo', 'doBar'];
		$arrayWithClassStringAndStaticMethod = [Foo::class, 'doBar'];
		$stringWithStaticMethod = 'Callables\\Foo::doFoo';
		$arrayWithInstanceMethod = [$this, 'doFoo'];
		die;
	}

	public function doBar(): Bar
	{

	}

	public function __invoke(): int
	{

	}

}
