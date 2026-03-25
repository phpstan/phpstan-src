<?php declare(strict_types = 1);

namespace Bug14188;

interface MyInterface {}
class A implements MyInterface {}
class B implements MyInterface {}

class MyFactory {
	/**
	 * @template T of MyInterface
	 *
	 * @param class-string<T> $class
	 *
	 * @return T
	 */
	public function create(string $class) {
		if ($class === A::class) {
			return new $class();
		}

		return new $class();
	}
}
