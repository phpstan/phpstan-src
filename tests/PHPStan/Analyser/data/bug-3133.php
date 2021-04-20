<?php

namespace Bug3133;

use function PHPStan\Testing\assertType;

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

		assertType('string&numeric', $arg);
	}

	/**
	 * @param string|bool|float|int|mixed[]|null $arg
	 */
	public function doBar($arg): void
	{
		if (\is_numeric($arg)) {
			assertType('float|int|(string&numeric)', $arg);
		}
	}

	/**
	 * @param numeric $numeric
	 * @param numeric-string $numericString
	 */
	public function doBaz(
		$numeric,
		string $numericString
	)
	{
		assertType('float|int|(string&numeric)', $numeric);
		assertType('string&numeric', $numericString);
	}

	/**
	 * @param numeric-string $numericString
	 */
	public function doLorem(
		string $numericString
	)
	{
		$a = [];
		$a[$numericString] = 'foo';
		assertType('array<int, \'foo\'>', $a);
	}

}
