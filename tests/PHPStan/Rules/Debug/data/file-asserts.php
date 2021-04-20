<?php

namespace FileAsserts;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	/**
	 * @param array<int> $a
	 */
	public function doFoo(array $a): void
	{
		assertType('array<int>', $a);
		assertType('array<string>', $a);
	}

	/**
	 * @param non-empty-array<int> $a
	 */
	public function doBar(array $a): void
	{
		assertType('array<int>&nonEmpty', $a);
		assertNativeType('array', $a);

		assertType('false', $a === []);
		assertType('true', $a !== []);

		assertNativeType('bool', $a === []);
		assertNativeType('bool', $a !== []);

		assertNativeType('false', $a === []);
		assertNativeType('true', $a !== []);
	}

	public function doBaz($a): void
	{
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertVariableCertainty(TrinaryLogic::createNo(), $b);

		assertVariableCertainty(TrinaryLogic::createYes(), $b);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $b);
	}

}
