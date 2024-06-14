<?php

namespace Bug560;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

assertVariableCertainty(TrinaryLogic::createMaybe(), $city);
assertType('mixed', $city);

if ($city ?? false) {
	assertVariableCertainty(TrinaryLogic::createYes(), $city);
	assertType('mixed~null', $city);
}

function (?string $s): void {
	if ($s ?? false) {
		assertVariableCertainty(TrinaryLogic::createYes(), $s);
		assertType('string' ,$s);
	}
};
