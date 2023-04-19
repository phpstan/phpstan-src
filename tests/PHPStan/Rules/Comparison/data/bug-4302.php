<?php declare(strict_types = 1);

namespace Bug4302;

class HelloWorld
{
	public function theMethod(MyInterface $object) {
		if (get_parent_class($object)) {
			return 1;
		}
	}
}

interface MyInterface {}
