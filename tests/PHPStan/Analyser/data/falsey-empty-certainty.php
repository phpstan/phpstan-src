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
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
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
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}

function justEmpty(): void
{
	assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	if (!empty($foo)) {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	} else {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	}
	assertVariableCertainty(TrinaryLogic::createNo(), $foo);
}

function maybeEmpty(): void
{
	if (rand() % 2) {
		$foo = 1;
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	if (!empty($foo)) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
}

function maybeEmptyUnset(): void
{
	if (rand() % 2) {
		$foo = 1;
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	if (!empty($foo)) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
		unset($foo);
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
}
