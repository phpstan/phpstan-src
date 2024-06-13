<?php declare(strict_types = 1); // onlyif PHP_VERSION_ID < 80000

namespace Bug7239;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param string[] $strings
	 */
	public function sayHello(array $arr, $strings): void
	{
		assertType('false', max([]));
		assertType('false', min([]));

		if (count($arr) > 0) {
			assertType('mixed', max($arr));
			assertType('mixed', min($arr));
		} else {
			assertType('false', max($arr));
			assertType('false', min($arr));
		}

		assertType('array', max([], $arr));
		assertType('array', min([], $arr));

		if (count($strings) > 0) {
			assertType('string', max($strings));
			assertType('string', min($strings));
		} else {
			assertType('false', max($strings));
			assertType('false', min($strings));
		}
	}
}
