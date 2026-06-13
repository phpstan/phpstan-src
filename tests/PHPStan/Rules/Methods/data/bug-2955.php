<?php declare(strict_types = 1);

namespace Bug2955Method;

class Foo {}

class Factory
{

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public function make(string $className): object
	{
		if ($className === \stdClass::class) {
			return (object) [];
		}

		return new $className();
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public function makeWrong(string $className): object
	{
		if ($className === \stdClass::class) {
			return new Foo();
		}

		return new $className();
	}

}
