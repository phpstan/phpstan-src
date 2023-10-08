<?php

namespace FalseyIssetCertainty;

use function PHPStan\Testing\assertVariableCertainty;
use PHPStan\TrinaryLogic;

function falseyIssetArrayDimFetch(): void
{
	$a = ['bar' => null];
	if (rand() % 3) {
		$a = ['bar' => 'hello'];
	}

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
	if (isset($a['bar'])) {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	} else {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	}

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
}

function falseyIssetUncertainArrayDimFetch(): void
{
	if (rand() % 2) {
		$a = ['bar' => null];
		if (rand() % 3) {
			$a = ['bar' => 'hello'];
		}
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	if (isset($a['bar'])) {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}

function falseyIssetVariable(): void
{
	if (rand() % 2) {
		$a = 'bar';
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	if (isset($a)) {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}
