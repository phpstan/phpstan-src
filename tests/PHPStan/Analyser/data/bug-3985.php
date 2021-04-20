<?php

namespace Bug3985;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{
	public function doFoo(array $array): void
	{
		foreach ($array as $val) {
			if (isset($foo[1])) {
				assertVariableCertainty(TrinaryLogic::createNo(), $foo);
			}
		}
	}

	public function doBar(): void
	{
		if (isset($foo[1])) {
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		}
	}
}
