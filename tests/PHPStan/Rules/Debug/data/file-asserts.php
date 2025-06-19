<?php

namespace FileAsserts;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertSuperType;
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

		assertSuperType('array<int|string>', $a);
		assertSuperType('array<string>', $a);
	}

	/**
	 * @param non-empty-array<int> $a
	 */
	public function doBar(array $a): void
	{
		assertType('non-empty-array<int>', $a);
		assertNativeType('array', $a);
		assertSuperType('mixed', $a);

		assertType('false', $a === []);
		assertType('true', $a !== []);

		assertNativeType('bool', $a === []);
		assertNativeType('bool', $a !== []);

		assertNativeType('false', $a === []);
		assertNativeType('true', $a !== []);

		assertSuperType('bool', $a === []);
		assertSuperType('bool', $a !== []);
		assertSuperType('mixed', $a === []);
		assertSuperType('string', $a === []);
		assertSuperType('never', $a === []);
	}

	public function doBaz($a): void
	{
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertVariableCertainty(TrinaryLogic::createNo(), $b);

		assertVariableCertainty(TrinaryLogic::createYes(), $b);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $b);
	}

	/**
	 * @param array{firstName: string, lastName?: string, sub: array{other: string}} $context
	 */
	public function arrayOffset(array $context) : void
	{
		assertVariableCertainty(TrinaryLogic::createYes(), $context['firstName']);
		assertVariableCertainty(TrinaryLogic::createYes(), $context['sub']);
		assertVariableCertainty(TrinaryLogic::createYes(), $context['sub']['other']);

		assertVariableCertainty(TrinaryLogic::createMaybe(), $context['lastName']);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $context['nonexistent']['somethingElse']);

		assertVariableCertainty(TrinaryLogic::createNo(), $context['sub']['nonexistent']);
		assertVariableCertainty(TrinaryLogic::createNo(), $context['email']);

		// Deliberate error:
		assertVariableCertainty(TrinaryLogic::createNo(), $context['firstName']);
	}

}
