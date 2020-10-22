<?php

namespace Bug2816;

use PHPStan\TrinaryLogic;
use function PHPStan\Analyser\assertType;
use function PHPStan\Analyser\assertVariableCertainty;

if (isset($_GET['x'])) {
	$a = 1;
}

assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
assertType('mixed', $a);

if (isset($a)) {
	echo "hello";
	assertVariableCertainty(TrinaryLogic::createYes(), $a);
	assertType('mixed~null', $a);
} else {
	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}

assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
assertType('mixed', $a);

if (isset($a)) {
	echo "hello2";
	assertVariableCertainty(TrinaryLogic::createYes(), $a);
	assertType('mixed~null', $a);
} else {
	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}

assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
assertType('mixed', $a);
