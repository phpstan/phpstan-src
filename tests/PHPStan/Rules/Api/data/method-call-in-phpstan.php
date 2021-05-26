<?php declare(strict_types = 1);

namespace PHPStan\MethodCall;

use PHPStan\Rules\Debug\DumpTypeRule;

class Foo
{

	public function doFoo(DumpTypeRule $rule): void
	{
		echo $rule->getNodeType();
	}

}
