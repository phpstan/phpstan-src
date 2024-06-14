<?php

namespace ThrowPoints\Throw_;

use Exception;
use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

function () {
	try {
		throw new Exception();
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	}
};

function () {
	try {
		if (random_int(0, 1)) {
			throw new Exception();
		}
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		$foo = 1;
		throw new Exception();
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};
