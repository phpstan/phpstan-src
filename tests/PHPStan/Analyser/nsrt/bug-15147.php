<?php declare(strict_types = 1);

namespace Bug15147;

use ArrayObject;
use function PHPStan\Testing\assertType;

class Test
{

	/**
	 * @param ArrayObject<array-key, array<mixed>|mixed> $alias
	 */
	public function populate(ArrayObject $alias): void
	{
	}

	public function test(): void
	{
		$alias = new ArrayObject();
		$this->populate($alias);
		// the parameter's value slot accepts anything, so it decides nothing -
		// but the object is in use, so the template's bound stands instead of
		// the never an untouched `new ArrayObject()` would resolve to
		assertType('ArrayObject<(int|string), mixed>', $alias);
	}

	public function untouched(): void
	{
		$alias = new ArrayObject();
		assertType('ArrayObject<*NEVER*, *NEVER*>', $alias);
	}

}
