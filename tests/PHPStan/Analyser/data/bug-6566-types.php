<?php // onlyif PHP_VERSION_ID >= 80000

namespace Bug6566Types;

use function PHPStan\Testing\assertType;

class A {
	public string $name;
}

class B {
	public string $name;
}

class C {

}

/**
 * @template T of A|B|C
 */
abstract class HelloWorld
{
	public function sayHelloBug(): void
	{
		$object = $this->getObject();
		assertType('T of Bug6566Types\A|Bug6566Types\B|Bug6566Types\C (class Bug6566Types\HelloWorld, argument)', $object);
		if ($object instanceof C) {
			assertType('T of Bug6566Types\C (class Bug6566Types\HelloWorld, argument)', $object);
			return;
		}
		assertType('T of Bug6566Types\A|Bug6566Types\B (class Bug6566Types\HelloWorld, argument)', $object);
		if ($object instanceof B) {
			assertType('T of Bug6566Types\B (class Bug6566Types\HelloWorld, argument)', $object);
			return;
		}
		assertType('T of Bug6566Types\A (class Bug6566Types\HelloWorld, argument)', $object);
		if ($object instanceof A) {
			assertType('T of Bug6566Types\A (class Bug6566Types\HelloWorld, argument)', $object);
			return;
		}
		assertType('*NEVER*', $object);
	}

	/**
	 * @return T
	 */
	abstract protected function getObject(): A|B|C;
}
