<?php

namespace FixCollectedFuncCall;

function foo(int $i): int { return $i; }
function bar(int $i): int { return $i; }

class Foo
{

	public function doFoo(): int
	{
		return foo(1)
			+ foo(2);
	}

}

echo foo(3);
