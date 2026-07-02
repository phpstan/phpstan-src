<?php // lint < 8.0

namespace ThrowPoints\IllegalArrayOffset;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\doesntThrow;

// Before PHP 8.0 an illegal (array/object) offset does not throw TypeError, so a
// non-throwing offset expression introduces no throw point and $foo is always assigned.

function () {
	try {
		$foo[doesntThrow()] = 0;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		[$foo[doesntThrow()]] = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		$foo[doesntThrow()] .= 0;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		[][doesntThrow()];
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};
