<?php

namespace Bug2273;

use function PHPStan\Testing\assertType;

class Foo {
	/**
	 * @param string[] $x
	 */
	public function doFoo(array $x): void
	{
		foreach ($x as $k => $v) {
			$x[$k] = \realpath($v);
			if ($x[$k] === false) {
				throw new \Exception();
			}
		}

		assertType('array<non-empty-string>', $x);
	}
}
