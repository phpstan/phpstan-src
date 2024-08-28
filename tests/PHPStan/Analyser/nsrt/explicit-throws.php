<?php

namespace ExplicitThrows;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	public function doFoo(): void
	{
		try {
			doFoo();
			$a = 1;
			throw new \InvalidArgumentException();
		} catch (\InvalidArgumentException $e) {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		}
	}

	public function doBar(): void
	{
		try {
			doFoo();
			$a = 1;
			$this->throwInvalidArgument();
		} catch (\InvalidArgumentException $e) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
		}
	}

	public function doBaz(): void
	{
		try {
			doFoo();
			$a = 1;
			$this->throwInvalidArgument();
			throw new \InvalidArgumentException();
		} catch (\InvalidArgumentException $e) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
		}
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private function throwInvalidArgument(): void
	{

	}

}
