<?php

namespace Bug2471;

use function PHPStan\Testing\assertType;

class A {
	public function __toString()
	{
		return 'foo';
	}
}

abstract class Foo {
	/**
	 * @return A[]
	 */
	abstract function doFoo(): array;

	public function test(): void
	{
		$y = $this->doFoo();

		$x = array_fill_keys($y, null);

		assertType('array<string, null>', $x);
	}
}
