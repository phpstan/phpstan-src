<?php declare(strict_types = 1);

namespace Bug2955;

/**
 * @template T of object
 * @param class-string<T> $className
 * @return T
 */
function test(string $className): object {
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
function test2(string $className): object {
	if ($className === \stdClass::class) {
		return new \stdClass();
	}

	return new $className();
}

class Foo {}

/**
 * @template T of object
 * @param class-string<T> $className
 * @return T
 */
function returnsWrongClass(string $className): object {
	if ($className === \stdClass::class) {
		return new Foo();
	}

	return new $className();
}

/**
 * @template T of object
 * @param class-string<T> $className
 * @return T
 */
function notPinnedByIsA(string $className): object {
	if (is_a($className, Foo::class, true)) {
		return new Foo();
	}

	return new $className();
}
