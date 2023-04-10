<?php declare(strict_types = 1);

namespace Bug4302b;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function theMethod(MyInterface $object) {
		assertType('class-string|false', get_parent_class($object));
	}
}

interface MyInterface {}
