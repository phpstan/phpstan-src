<?php

namespace ThrowPoints\And_;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\doesntThrow;
use function ThrowPoints\Helpers\maybeThrows;

function () {
	try {
		$foo = (doesntThrow() || doesntThrow());
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		$foo = (doesntThrow() || maybeThrows());
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		$foo = (doesntThrow() or doesntThrow());
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		$foo = (doesntThrow() or maybeThrows());
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};
