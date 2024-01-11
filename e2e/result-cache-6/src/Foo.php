<?php

namespace TestResultCache6;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(Bar $b): void
	{
		echo $b->s;
	}

}
