<?php

namespace DependentVariablesArrowFunction;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

function (bool $b): void
{

	if ($b) {
		$foo = 'foo';
	}

	fn () => assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

	fn () => $b ? assertVariableCertainty(TrinaryLogic::createYes(), $foo) : assertVariableCertainty(TrinaryLogic::createNo(), $foo);

	fn ($b) => $b ? assertVariableCertainty(TrinaryLogic::createMaybe(), $foo) : assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

	fn ($foo) => $b ? assertVariableCertainty(TrinaryLogic::createYes(), $foo) : assertVariableCertainty(TrinaryLogic::createYes(), $foo);

	fn ($foo) => assertVariableCertainty(TrinaryLogic::createYes(), $foo);

};
