<?php

namespace Bug10224;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function foo(bool $condition): ?string
{
	if ($condition) {
		$foo = 'bar';
	}
	if ($foo ?? null) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
		return $foo;
	}
	return null;
}

function bar(bool $condition, $a): ?string
{
	if ($condition) {
		$foo = $a;
	}
	if ($foo ?? null) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
		assertType("mixed~null", $foo);
		return $foo;
	}
	return null;
}
