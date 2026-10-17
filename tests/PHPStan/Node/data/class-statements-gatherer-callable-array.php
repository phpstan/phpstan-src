<?php

namespace ClassStatementsGathererCallableArray;

class Foo
{

	public function test(): void
	{
		$a = [$this, 'method'];
		$b = [self::class, 'method'];
		$c = [static::class, 'method'];
		$d = [Foo::class, 'method'];

		$x1 = ['SomeClass', 'method'];
		$x2 = [\stdClass::class, 'method'];
		$x3 = ['foo', 'bar'];
		$x4 = [1, 2];
		[, $x5] = ['foo', 'bar'];
	}

}
