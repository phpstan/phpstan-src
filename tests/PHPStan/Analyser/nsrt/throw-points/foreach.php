<?php

namespace ThrowPoints\Foreach_;

use Exception;
use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\maybeThrows;

function (iterable $iterable) {
	try {
		foreach ($iterable as $v) {
			maybeThrows();
		}
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		foreach ([] as $v) {
			maybeThrows();
		}
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

