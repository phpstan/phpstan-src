<?php declare(strict_types = 1);

namespace Bug7706;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class HelloWorld
{
	public function test(): void
	{
		$entity = null;
		if (rand(0, 10) < 5) {
			$entity = rand(0, 10) < 5 ? 1 : null;
			$update = true;
		}

		if (!$entity) {
			$update = false;
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $update);
		echo $update;
	}
}
