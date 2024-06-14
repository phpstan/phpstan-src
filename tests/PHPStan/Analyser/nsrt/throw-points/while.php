<?php

namespace ThrowPoints\While_;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\maybeThrows;

function () {
	try {
		while (random_int(0, 1)) {
			maybeThrows();
		}
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		while (false) {
			maybeThrows();
		}
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};
