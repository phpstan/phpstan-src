<?php

namespace KeyOfType;

use function PHPStan\Testing\assertType;

class Foo
{

	public const JFK = 'jfk';
	public const LGA = 'lga';

	private const ALL = [
		self::JFK => 'John F. Kennedy Airport',
		self::LGA => 'La Guardia Airport',
	];

	/**
	 * @param key-of<self::ALL> $code
	 */
	public static function foo(string $code): void
	{
		assertType('\'jfk\'|\'lga\'', $code);
	}

	/**
	 * @param key-of<'jfk'> $code
	 */
	public static function bar(string $code): void
	{
		assertType('string', $code);
	}

	/**
	 * @param key-of<'jfk'|'lga'> $code
	 */
	public static function baz(string $code): void
	{
		assertType('string', $code);
	}
}
