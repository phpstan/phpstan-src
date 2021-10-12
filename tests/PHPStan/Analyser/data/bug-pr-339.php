<?php

namespace BugPr339;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
assertVariableCertainty(TrinaryLogic::createMaybe(), $c);
assertType('mixed', $a);
assertType('mixed', $c);

if ($a || $c) {
	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	assertVariableCertainty(TrinaryLogic::createMaybe(), $c);
	assertType('mixed', $a);
	assertType('mixed', $c);
	if ($a) {
		assertType("mixed~0|0.0|''|'0'|array{}|false|null", $a);
		assertType('mixed', $c);
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	}

	if ($c) {
		assertType('mixed', $a);
		assertType("mixed~0|0.0|''|'0'|array{}|false|null", $c);
		assertVariableCertainty(TrinaryLogic::createYes(), $c);
	}
} else {
	assertType("0|0.0|''|'0'|array{}|false|null", $a);
	assertType("0|0.0|''|'0'|array{}|false|null", $c);
}
