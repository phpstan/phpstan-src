<?php

namespace Bug5843;

function foo(object $object): void
{
	switch ($object::class) {
		case \DateTime::class:
			$object->modify('+1 day');
			break;
		case \Throwable::class:
			$object->getPrevious();
			break;
	}
}
