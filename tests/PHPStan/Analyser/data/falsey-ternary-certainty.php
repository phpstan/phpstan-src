<?php

namespace FalseyTernaryCertainty;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;
use PHPStan\TrinaryLogic;

function getFoo():mixed {
	return 1;
}

function falseyTernaryArrayDimFetchOnProperty(): void
{
	$a = new \stdClass();
	$a->bar = null;
	if (rand() % 3) {
		$a->bar = 'hello';
	}

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
	isset($a->bar)?
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	:
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	;

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
}

function falseyTernaryUncertainArrayDimFetchOnProperty(): void
{
	if (rand() % 2) {
		$a = new \stdClass();
		$a->bar = null;
		$a = ['bar' => null];
		if (rand() % 3) {
			$a->bar = 'hello';
		}
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	isset($a->bar) ?
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	:
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a)
	;

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}

function falseyTernaryUncertainPropertyFetch(): void
{
	if (rand() % 2) {
		$a = new \stdClass();
		if (rand() % 3) {
			$a->x = 'hello';
		}
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	isset($a->x) ?
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	:
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a)
	;

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}

function falseyTernaryArrayDimFetch(): void
{
	$a = ['bar' => null];
	if (rand() % 3) {
		$a = ['bar' => 'hello'];
	}

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
	isset($a['bar']) ?
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	:
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	;

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
}

function falseyTernaryUncertainArrayDimFetch(): void
{
	if (rand() % 2) {
		$a = ['bar' => null];
		if (rand() % 3) {
			$a = ['bar' => 'hello'];
		}
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	isset($a['bar']) ?
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	:
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a)
	;

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}

function falseyTernaryVariable(): void
{
	if (rand() % 2) {
		$a = 'bar';
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	isset($a) ?
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	:
		assertVariableCertainty(TrinaryLogic::createNo(), $a)
	;

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}

function nullableVariable(): void
{
	$a = 'bar';
	if (rand() % 2) {
		$a = null;
	}

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
	isset($a) ?
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	:
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	;

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
}

function nonNullableVariable(): void
{
	$a = 'bar';

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
	isset($a) ?
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	:
		assertVariableCertainty(TrinaryLogic::createNo(), $a)
	;

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
}

function nonNullableVariableShort(): void
{
	$a = 'bar';

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
	isset($a) ?:
		assertVariableCertainty(TrinaryLogic::createNo(), $a)
	;

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
}

function falseyTernaryNullableVariable(): void
{
	if (rand() % 2) {
		$a = 'bar';
		if (rand() % 3) {
			$a = null;
		}
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	isset($a) ?
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	:
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a)
	;

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}

function falseyMixedTernaryVariable(): void
{
	if (rand() % 2) {
		$a = getFoo();
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	isset($a) ?
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	:
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a)
	;

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}

function falseySubtractedMixedTernaryVariable(): void
{
	if (rand() % 2) {
		$a = getFoo();
		if ($a === null) {
			return;
		}
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	isset($a) ?
		assertVariableCertainty(TrinaryLogic::createYes(), $a)
	:
		assertVariableCertainty(TrinaryLogic::createNo(), $a)
	;

	assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
}
