<?php

namespace Bug4741;

use function PHPStan\Testing\assertType;

class Foo
{

	public function isAnObject($object): void {
		$class = get_class($object);
		assertType('class-string|false', $class);
	}

}
