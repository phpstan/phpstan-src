<?php

namespace Bug3133;

use function PHPStan\Analyser\assertType;

class Foo
{

	/**
	 * @param string[]|string $arg
	 */
	public function doFoo($arg): void
	{
		if (!is_numeric($arg)) {
			assertType('array<string>|string', $arg);
			return;
		}

		assertType('string', $arg);
	}

	/**
	 * @param string|bool|float|int|mixed[]|null $arg
	 */
	public function doBar($arg): void
	{
		if (\is_numeric($arg)) {
			assertType('float|int|string', $arg);
		}
	}

}
