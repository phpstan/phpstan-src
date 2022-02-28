<?php

namespace Bug5893;


class Test
{
	/**
	 * @template T of object
	 * @psalm-param T $object
	 * @psalm-return class-string<T>
	 */
	public static function getClass($object)
	{
		return get_class($object);
	}
}

/**
 * @phpstan-template T of object
 */
class Foo {
	/** @phpstan-param T $object */
	public function foo(object $object): string {
		if (method_exists($object, '__toString') && null !== $object->__toString()) {
			return $object->__toString();
		}

		return Test::getClass($object);
	}
}
