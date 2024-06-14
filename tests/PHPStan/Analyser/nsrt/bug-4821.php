<?php

namespace Bug4821;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class HelloWorld
{
	public function sayHello(): void
	{
		try {
			$object = new HelloWorld();
			$method = new \ReflectionMethod($object, 'nonExisting');
			$method->invoke($object);
			return;
		} catch (\ReflectionException $e) {
			assertVariableCertainty(TrinaryLogic::createYes(), $object);
			assertVariableCertainty(TrinaryLogic::createMaybe(), $method);
		}
	}
}
