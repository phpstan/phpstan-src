<?php declare(strict_types = 1);

namespace Bug14188Nsrt;

use function PHPStan\Testing\assertType;

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
			assertType("'Bug14188Nsrt\\\\A'", $class);
			assertType('Bug14188Nsrt\A&T of Bug14188Nsrt\MyInterface (method Bug14188Nsrt\MyFactory::create(), argument)', new $class());
			return new $class();
		}

		return new $class();
	}
}
