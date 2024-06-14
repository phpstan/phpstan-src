<?php

namespace EvalImplicitThrow;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

function evalString(string $evalString) {
	try {
		eval($evalString);
	} catch (\Throwable $exception) {
		$foo = 1;
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
}
