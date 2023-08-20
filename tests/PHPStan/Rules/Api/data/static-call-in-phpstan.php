<?php declare(strict_types = 1);

namespace PHPStan\StaticMethodCall;

use PHPStan\Command\CommandHelper;

class Foo
{

	public function doFoo(): void
	{
		CommandHelper::begin();
	}

}
