<?php

namespace Trim;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param 'foo' $foo
	 * @param 'foo'|'bar' $fooOrBar
	 * @param string $string
	 */
	public function doTrim($foo, $fooOrBar, $string): void
	{
		assertType('string', trim($string, $foo));
		assertType('string', ltrim($string, $foo));
		assertType('string', rtrim($string, $foo));

		assertType('lowercase-string', trim($foo, $string));
		assertType('lowercase-string', ltrim($foo, $string));
		assertType('lowercase-string', rtrim($foo, $string));
		assertType('\'\'|\'foo\'', trim($foo, $fooOrBar));
		assertType('\'\'|\'foo\'', ltrim($foo, $fooOrBar));
		assertType('\'\'|\'foo\'', rtrim($foo, $fooOrBar));

		assertType('lowercase-string', trim($fooOrBar, $string));
		assertType('lowercase-string', ltrim($fooOrBar, $string));
		assertType('lowercase-string', rtrim($fooOrBar, $string));
		assertType('\'\'|\'bar\'', trim($fooOrBar, $foo));
		assertType('\'\'|\'bar\'', ltrim($fooOrBar, $foo));
		assertType('\'\'|\'bar\'', rtrim($fooOrBar, $foo));
	}

}
