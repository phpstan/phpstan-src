<?php

namespace FunctionCallStatementNoSideEffects;

use PHPStan\TrinaryLogic;

class Foo
{

	public function doFoo()
	{
		printf('%s', 'test');
		sprintf('%s', 'test');
	}

	public function doBar(string $s)
	{
		\PHPStan\Testing\assertType('string', $s);
		\PHPStan\Testing\assertNativeType('string', $s);
		\PHPStan\Testing\assertVariableCertainty(TrinaryLogic::createYes(), $s);
	}

}
