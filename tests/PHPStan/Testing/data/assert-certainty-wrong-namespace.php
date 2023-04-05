<?php // lint >= 8.0

namespace WrongAssertCertaintyNamespace;

use PHPStan\TrinaryLogic;
use function SomeWrong\Namespace\assertVariableCertainty;

function doFoo(string $s) {
	assertVariableCertainty(TrinaryLogic::createMaybe(), $s);
}
