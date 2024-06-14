<?php

namespace VarInAndOutOfFunction;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

/** @var int $foo */

assertVariableCertainty(TrinaryLogic::createYes(), $foo);

function doFoo(): void
{
	if (rand(0, 1)) {
		$foo = 1;
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

	/** @var int $foo */

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
}
