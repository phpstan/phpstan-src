<?php

namespace ValueOfType;

use function PHPStan\Testing\assertType;

class Foo
{

	private const ALL = [
		'jfk',
		'lga',
	];

	/**
	 * @param value-of<self::ALL> $code
	 */
	public static function foo(string $code): void
	{
		assertType('\'jfk\'|\'lga\'', $code);
	}

	/**
	 * @param value-of<'jfk'> $code
	 */
	public static function bar(string $code): void
	{
		assertType('string', $code);
	}

	/**
	 * @param value-of<'jfk'|'lga'> $code
	 */
	public static function baz(string $code): void
	{
		assertType('string', $code);
	}
}
