<?php

namespace Bug4343;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

function (array $a) {
	if (count($a) > 0) {
		$test = new \stdClass();
	}

	foreach ($a as $my) {
		assertVariableCertainty(TrinaryLogic::createYes(), $test);
	}
};
