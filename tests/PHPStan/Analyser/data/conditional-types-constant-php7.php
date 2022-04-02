<?php

namespace ConditionalTypesConstantPhp7;

use function PHPStan\Testing\assertType;

abstract class Test
{
	/**
	 * @return (PHP_MAJOR_VERSION is 7 ? true : false)
	 */
	abstract public function isPHP7(): bool;

	public function testIsPHP7(): void
	{
		assertType('true', $this->isPHP7());
	}
}
