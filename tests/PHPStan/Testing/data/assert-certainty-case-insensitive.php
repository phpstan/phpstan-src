<?php // lint >= 8.0

namespace MissingAssertCertaintyCaseSensitive;

use PHPStan\TrinaryLogic;

function doFoo(string $s) {
	assertvariablecertainty(TrinaryLogic::createMaybe(), $s);
}
