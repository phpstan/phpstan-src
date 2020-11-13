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

}
