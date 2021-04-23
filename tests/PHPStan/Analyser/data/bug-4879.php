<?php

namespace Bug4879;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class HelloWorld
{
	public function sayHello(bool $bool1): void
	{
		try {
			if ($bool1) {
				throw new \Exception();
			}

			$var = 'foo';

			$this->test();
		} catch (\Throwable $ex) {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $var);
		}
	}

	public function sayHello2(bool $bool1): void
	{
		try {
			if ($bool1) {
				throw new \Exception();
			}

			$var = 'foo';

			$this->test();
		} catch (\Exception $ex) {
			assertVariableCertainty(TrinaryLogic::createNo(), $var);
		}
	}

	public function test(): void
	{
		return;
	}
}
