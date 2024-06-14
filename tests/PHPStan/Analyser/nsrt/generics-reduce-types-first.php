<?php

// inspired by: https://github.com/microsoft/TypeScript/pull/5738

namespace GenericsReduceTypesFirst;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @template T
	 * @param T|string $p
	 * @return T
	 */
	public function doFoo($p)
	{

	}

	/**
	 * @param string[]|string $a
	 * @param string|string[] $b
	 * @param string[] $c
	 */
	public function doBar($a, $b, $c, string $d)
	{
		assertType('array<string>', $this->doFoo($a));
		assertType('array<string>', $this->doFoo($b));
		assertType('array<string>', $this->doFoo($c));
		assertType('string', $this->doFoo($d));
	}

}
