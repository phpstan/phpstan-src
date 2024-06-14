<?php

namespace ThrowPoints\Switch_;

use Exception;
use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\maybeThrows;

function () {
	try {
		switch (random_int(0, 1)) {
			case 0:
				maybeThrows();
		}
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		switch (random_int(0, 1)) {
			default:
				maybeThrows();
		}
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		switch (random_int(0, 1)) {
			default:
				throw new Exception();
		}
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	}
};
