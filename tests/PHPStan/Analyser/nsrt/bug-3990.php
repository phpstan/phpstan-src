<?php

namespace Bug3990;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

function doFoo(array $config): void
{
	extract($config);

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);

	if (isset($a)) {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}
