<?php

namespace Discussion9972;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class HelloWorld
{
	public function f1(bool $myBool): void
	{
		if ($myBool) {
			$myObject = new DateTime();
		}

		$this->helper($myBool);

		if ($myBool) {
			assertVariableCertainty(TrinaryLogic::createYes(), $myObject);
		}
	}

	protected function helper(bool $input): void
	{
	}
}
