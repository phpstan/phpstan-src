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

	public function sayHello2(): void
	{
		$method = rand(0, 1) ? 'nonExisting' : 'sayFoo';
		try {
			$object = new HelloWorld();
			$method = new \ReflectionMethod($object, $method);
			$method->invoke($object);
			return;
		} catch (\ReflectionException $e) {
			assertVariableCertainty(TrinaryLogic::createYes(), $object);
			assertVariableCertainty(TrinaryLogic::createYes(), $method);
		}
	}

	public function sayFoo(): void
	{

	}
}
