<?php // lint >= 8.0

namespace Bug6566;

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
		if (!$object instanceof C) {
			echo $object->name;
		}
	}

	/**
	 * @return T
	 */
	abstract protected function getObject(): A|B|C;
}
