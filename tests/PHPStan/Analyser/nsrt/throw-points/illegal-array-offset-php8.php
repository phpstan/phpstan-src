<?php // lint >= 8.0

namespace ThrowPoints\IllegalArrayOffset;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\doesntThrow;

// doesntThrow() never throws by itself, but on PHP 8.0+ its mixed return value used
// as an array/string offset may be an array or an object, which throws TypeError.
// That TypeError is the only throw point here, so $foo may be left unassigned.

function () {
	try {
		$foo[doesntThrow()] = 0;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		[$foo[doesntThrow()]] = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		$foo[doesntThrow()] .= 0;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		[][doesntThrow()];
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};
