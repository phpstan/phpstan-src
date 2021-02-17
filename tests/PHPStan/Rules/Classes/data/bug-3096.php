<?php declare(strict_types = 1);

namespace Bug3096;

class HelloWorld
{
	/**
	 * @param class-string<\DateTimeInterface> $class
	 */
	public static function sayHello(\DateTimeInterface $object, string $class): void
	{
		assert($object instanceof $class);
	}
}
