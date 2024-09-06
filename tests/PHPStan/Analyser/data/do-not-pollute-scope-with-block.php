<?php

namespace DoNotPolluteScopeWithBlock;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	public function doFoo(): void
	{
		$a = 1;
		{
			$b = 2;
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType('1', $a);

		assertVariableCertainty(TrinaryLogic::createMaybe(), $b);
		assertType('2', $b);
	}

}
