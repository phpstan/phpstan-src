<?php

namespace FalsyIsset;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;
use PHPStan\TrinaryLogic;

function mixedIsset(mixed $m): void
{
	if (isset($m)) {
		assertType("mixed~null", $m);
	} else {
		assertType("null", $m);
	}
}

function nullableVariable(?string $a): void
{
	if (isset($a)) {
		assertType("string", $a);
	} else {
		assertType("null", $a);
	}
}

function nullableUnionVariable(null|string|int $a): void
{
	if (isset($a)) {
		assertType("int|string", $a);
	} else {
		assertType("null", $a);
	}
}

