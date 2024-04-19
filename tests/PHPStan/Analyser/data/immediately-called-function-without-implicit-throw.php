<?php

namespace ImmediatelyCalledFunctionWithoutImplicitThrow;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

try {
	$result = array_map('ucfirst', []);
} finally {
	assertVariableCertainty(TrinaryLogic::createYes(), $result);
}

class SomeClass
{

	public function noThrow(): void
	{
	}

	public function testFirstClassCallableNoThrow(): void
	{
		try {
			$result = array_map($this->noThrow(...), []);
		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $result);
		}
	}

}
