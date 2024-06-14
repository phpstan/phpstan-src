<?php

namespace Bug2806;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

function (): void {
	for (;;) {
		$a = "hello";
		break;
	}

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
};
