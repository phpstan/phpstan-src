<?php

namespace AssertMethods;

use stdClass;
use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @template ExpectedType of object
	 * @param class-string<ExpectedType> $class
	 * @phpstan-assert iterable<ExpectedType|class-string<ExpectedType>> $value
	 *
	 * @param iterable<object|string> $value
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return void
	 */
	public static function doFoo($value, string $class): void
	{

	}

	public function doBar($mixed)
	{
		self::doFoo($mixed, stdClass::class);
		assertType('iterable<class-string<stdClass>|stdClass>', $mixed);
	}

	/**
	 * @template ExpectedType of object
	 * @param class-string<ExpectedType> $class
	 * @phpstan-assert iterable<ExpectedType|class-string<ExpectedType>> $value
	 *
	 * @param iterable<object|string> $value
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return void
	 */
	public function doBaz($value, string $class): void
	{

	}

	public function doLorem($mixed)
	{
		$this->doBaz($mixed, stdClass::class);
		assertType('iterable<class-string<stdClass>|stdClass>', $mixed);
	}

}

/** @template T */
class Bar
{

	/**
	 * @phpstan-assert T $arg
	 */
	public function doFoo($arg): void
	{

	}

	/**
	 * @param Bar<stdClass> $bar
	 */
	public function doBar(Bar $bar, object $object): void
	{
		assertType('object', $object);
		$bar->doFoo($object);
		assertType(stdClass::class, $object);
	}

}
