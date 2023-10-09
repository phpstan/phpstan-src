<?php

namespace FalseyIssetCertainty;

use function PHPStan\Testing\assertType;
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

function falseyIssetWithAssignment(): void
{
	if (rand() % 2) {
		$x = ['x' => 1];
	}

	if (isset($x[$z = getFoo()])) {
		assertVariableCertainty(TrinaryLogic::createYes(), $z);
		assertVariableCertainty(TrinaryLogic::createYes(), $x);

	} else {
		assertVariableCertainty(TrinaryLogic::createYes(), $z);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $x);
	}

	assertVariableCertainty(TrinaryLogic::createYes(), $z);
	assertVariableCertainty(TrinaryLogic::createMaybe(), $x);
}

function falseyIssetUncertainPropertyFetch(): void
{
	if (rand() % 2) {
		$a = new \stdClass();
		if (rand() % 3) {
			$a->x = 'hello';
		}
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	if (isset($a->x)) {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}

function justIsset(): void
{
	if (isset($foo)) {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	}
}

function maybeIsset(): void
{
	if (rand() % 2) {
		$foo = 1;
	}
	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	if (isset($foo)) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
		assertType('1', $foo);
	}
}
