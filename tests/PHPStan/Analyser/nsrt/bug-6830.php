<?php

namespace Bug6830Nsrt;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function test(bool $do): void {

	if ($do) {

		$x = 9999;
	}

	foreach([1, 2, 3] as $whatever) {

		if ($do) {

			assertVariableCertainty(TrinaryLogic::createYes(), $x);
			assertType('123|9999', $x);

			if ($x) {

				$x = 123;
			}
		}
	}
}
