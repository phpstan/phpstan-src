<?php

namespace ConditionalTypesConstant;

use function PHPStan\Testing\assertType;

abstract class Test
{
	/**
	 * @return (PHP_MAJOR_VERSION is 8 ? true : false)
	 */
	abstract public function isPHP8(): bool;

	public function testIsPHP8(): void
	{
		assertType('true', $this->isPHP8());
	}
}
