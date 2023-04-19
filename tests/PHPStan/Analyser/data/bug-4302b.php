<?php declare(strict_types = 1);

namespace Bug4302b;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function theMethod(MyInterface $interface, MyClass $class, MySubClass $subclass) {
		assertType('class-string|false', get_parent_class($interface));
		assertType('false', get_parent_class($class));
		assertType("'Bug4302b\\\\MyClass'", get_parent_class($subclass));
	}
}

interface MyInterface {}

class MyClass implements MyInterface {}

class MySubClass extends MyClass {}
