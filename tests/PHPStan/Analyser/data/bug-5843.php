<?php

namespace Bug5843;

use function PHPStan\Testing\assertType;

function foo(object $object): void
{
	switch ($object::class) {
		case \DateTime::class:
			assertType(\DateTime::class, $object);
			$object->modify('+1 day');
			break;
		case \Throwable::class:
			assertType(\Throwable::class, $object);
			$object->getPrevious();
			break;
	}
}
