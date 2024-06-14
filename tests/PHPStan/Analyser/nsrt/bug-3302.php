<?php

namespace Bug3302;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class HelloWorld
{
	public function sayHello(int $b): int
	{
		try {
			if ($b === 1) {
				throw new \Exception();
			} else {
				$a = 2;
			}
		} catch (\Exception $e) {
			$a = 1;
		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			return $a;
		}
	}

}
