<?php

namespace FalseyEmptyCertainty;

use function PHPStan\Testing\assertVariableCertainty;
use PHPStan\TrinaryLogic;

function falseyEmptyArrayDimFetch(): void
{
	if (rand() % 2) {
		$a = ['bar' => null];
		if (rand() % 3) {
			$a = ['bar' => 'hello'];
		}
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	if (empty($a['bar'])) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}

function falseyEmptyUncertainPropertyFetch(): void
{
	if (rand() % 2) {
		$a = new \stdClass();
		if (rand() % 3) {
			$a->x = 'hello';
		}
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	if (empty($a->x)) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}
