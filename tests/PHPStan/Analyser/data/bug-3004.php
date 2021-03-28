<?php

namespace Bug3004;

use PHPStan\TrinaryLogic;
use function PHPStan\Analyser\assertVariableCertainty;

class HelloWorld
{
	public function sayHello(): void
	{
		try {
			$a = $this->getA();
		} catch (\InvalidArgumentException $e) {
			$a = 2;
		} finally {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		}
	}

	private function getA(): int
	{
		throw new \DomainException('test');
	}
}
