<?php

namespace Bug9704;

use DateTime;
use DateTimeImmutable;
use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @var array<string, class-string>
	 */
	private const TYPES = [
		'foo' => DateTime::class,
		'bar' => DateTimeImmutable::class,
	];

	/**
	 * @template M of self::TYPES
	 * @template T of key-of<M>
	 * @param T $type
	 *
	 * @return new<M[T]>
	 */
	public static function get(string $type) : object
	{
		$class = self::TYPES[$type];

		return new $class('now');
	}

	/**
	 * @template T of key-of<self::TYPES>
	 * @param T $type
	 *
	 * @return new<self::TYPES[T]>
	 */
	public static function get2(string $type) : object
	{
		$class = self::TYPES[$type];

		return new $class('now');
	}
}

assertType(DateTime::class, Foo::get('foo'));
assertType(DateTimeImmutable::class, Foo::get('bar'));

assertType(DateTime::class, Foo::get2('foo'));
assertType(DateTimeImmutable::class, Foo::get2('bar'));


