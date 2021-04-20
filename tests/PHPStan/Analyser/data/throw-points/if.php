<?php

namespace ThrowPoints\If_;

use Exception;
use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\maybeThrows;

function () {
	try {
		if (random_int(0, 1)) {
			maybeThrows();
		}
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		if (random_int(0, 1)) {
		} else {
			maybeThrows();
		}
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		if (random_int(0, 1)) {
		} elseif (random_int(0, 1)) {
			maybeThrows();
		}
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};
