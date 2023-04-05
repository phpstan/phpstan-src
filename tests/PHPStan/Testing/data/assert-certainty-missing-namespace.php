<?php // lint >= 8.0

namespace MissingAssertCertaintyNamespace;

use PHPStan\TrinaryLogic;

function doFoo(string $s) {
	assertVariableCertainty(TrinaryLogic::createMaybe(), $s);
}
