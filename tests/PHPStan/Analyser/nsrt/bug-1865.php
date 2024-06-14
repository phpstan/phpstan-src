<?php

namespace Bug1865;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

function someFunction() {
	$a = (bool) random_int(0, 1);

	if ($a) {
		$key = 'xyz';
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $key);

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createYes(), $key);
		echo $key;
	} else {
		assertVariableCertainty(TrinaryLogic::createNo(), $key);
	}
}
