<?php

namespace Bug1219;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	function test(): int
	{
		try {
			$var = 1;
		} catch (\Throwable $e) {
			switch ($e->getCode()) {
				case 1:
					return 0;
				default:
					return -1;
			}
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $var);

		return $var;
	}

}
