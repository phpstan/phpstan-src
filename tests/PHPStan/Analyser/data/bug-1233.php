<?php

namespace Bug1233;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function toArray($value): array
	{
		assertType('mixed', $value);
		if (is_array($value)) {
			assertType('array', $value);
			return $value;
		}

		assertType('mixed~array', $value);

		if (is_iterable($value)) {
			assertType('Traversable<mixed, mixed>', $value);
			return iterator_to_array($value);
		}

		assertType('mixed~array', $value);

		throw new \LogicException();
	}
}
