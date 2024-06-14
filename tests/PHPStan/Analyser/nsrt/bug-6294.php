<?php

namespace Bug6294;

use function PHPStan\Testing\assertType;

class HelloWorld1
{
	/**
	 * @phpstan-param object $object
	 * @phpstan-param class-string<HelloWorld1> $classString
	 * @phpstan-return HelloWorld1|null
	 */
	public function sayHello(object $object, $classString): ?object
	{
		if ($classString === get_class($object)) {
			assertType(HelloWorld1::class, $object);

			return $object;
		}

		return null;
	}

	/**
	 * @phpstan-param HelloWorld1 $object
	 * @phpstan-return HelloWorld1|null
	 */
	public function sayHello2(object $object, object $object2): ?object
	{
		if (get_class($object2) === get_class($object)) {
			assertType(HelloWorld1::class, $object);

			return $object;
		}

		return null;
	}
}
