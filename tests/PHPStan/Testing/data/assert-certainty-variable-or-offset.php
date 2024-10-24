<?php

namespace AssertCertaintyVariableOrOffset;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

/**
 * @param array{} $context
 */
function someMethod(array $context) : void
{
	assertVariableCertainty(TrinaryLogic::createNo(), $context);
	assertVariableCertainty(TrinaryLogic::createYes(), $context['email']);
}
