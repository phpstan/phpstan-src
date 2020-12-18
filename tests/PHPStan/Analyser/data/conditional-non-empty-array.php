<?php

namespace ConditionalNonEmptyArray;

use PHPStan\TrinaryLogic;
use function PHPStan\Analyser\assertType;
use function PHPStan\Analyser\assertVariableCertainty;

class Foo
{

	public function doFoo(array $a): void
	{
		foreach ($a as $val) {
			$foo = 1;
		}

		assertType('array', $a);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

		if (count($a) > 0) {
			assertType('array&nonEmpty', $a);
			assertVariableCertainty(TrinaryLogic::createYes(), $foo);
		} else {
			assertType('array()', $a);
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		}
	}

}
