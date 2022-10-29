<?php

namespace TestResultCache5;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(Bar $b): void
	{
		$b->doBar($var);
		assertType('true', $var instanceof \Exception);
	}

}
