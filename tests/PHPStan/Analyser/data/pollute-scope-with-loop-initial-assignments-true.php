<?php

namespace PolluteScopeWithLoopInitialAssignmentsTrue;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function forAlwaysIterates(): void
{
	$var1 = null;
	for ($i = 0; $i < 10; $i++) {
		$var1 = 1;
		$var2 = 2;
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $i);
	assertType('1', $var1);
	assertVariableCertainty(TrinaryLogic::createYes(), $var2);
}

function forMaybeIterates(): void
{
	$var1 = null;
	for ($i = 0; mt_rand(0, 1) === 0; $i++) {
		$var1 = 1;
		$var2 = 2;
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $i);
	assertType('1|null', $var1);
	assertVariableCertainty(TrinaryLogic::createMaybe(), $var2);
}

function forNeverIterates(): void
{
	$var1 = null;
	for ($i = 0; $i < 0; $i++) {
		$var1 = 1;
		$var2 = 2;
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $i);
	assertType('null', $var1);
	assertVariableCertainty(TrinaryLogic::createNo(), $var2);
}
