<?php

namespace InvalidAssignVar;

class Foo
{

	public function doFoo(
		?\stdClass $a
	): void
	{
		$a?->foo = 'bar';
		$a?->foo->bar = 'bar';
		$a?->foo->bar['foo'] = 'bar';

		[$a?->foo->bar] = 'test';
		[$a?->foo->bar => $b, $f?->foo->bar => $c] = 'test';

		$c = 'foo';
	}

	public function doBar(
		\stdClass $s
	)
	{
		$s->foo = 'bar';
		$d = 'foo';
		$s['test'] = 'baz';
		\stdClass::$foo = 'bar';

		$s->foo() = 'test';

		[$s->foo()] = ['test'];
		[$s] = ['test'];
	}

	public function doBaz(?\stdClass $a, \stdClass $b)
	{
		$x = &$a?->bar->foo;
		$y = &$a?->bar;
		$z = $b?->bar;
	}


}
