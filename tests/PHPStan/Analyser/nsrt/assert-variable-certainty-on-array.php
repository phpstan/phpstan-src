<?php

declare(strict_types=1);

namespace AssertVariableCertaintyOnArray;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{
	/**
	 * @param array{firstName: string, lastName?: string, sub: array{other: string}} $context
	 */
	public function __invoke(array $context) : void
	{
		assertVariableCertainty(TrinaryLogic::createYes(), $context['firstName']);
		assertVariableCertainty(TrinaryLogic::createYes(), $context['sub']);
		assertVariableCertainty(TrinaryLogic::createYes(), $context['sub']['other']);

		assertVariableCertainty(TrinaryLogic::createMaybe(), $context['lastName']);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $context['nonexistent']['somethingElse']);

		assertVariableCertainty(TrinaryLogic::createNo(), $context['sub']['nonexistent']);
		assertVariableCertainty(TrinaryLogic::createNo(), $context['email']);
	}

}
