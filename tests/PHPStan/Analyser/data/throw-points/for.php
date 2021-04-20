<?php

namespace ThrowPoints\For_;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\maybeThrows;

function () {
	try {
		for (;random_int(0, 1);) {
			maybeThrows();
		}
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};
