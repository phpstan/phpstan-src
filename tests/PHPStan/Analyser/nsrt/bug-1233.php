<?php

namespace Bug1233;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function toArray($value): array
	{
		assertType('mixed', $value);
		if (is_array($value)) {
			assertType('array<mixed, mixed>', $value);
			return $value;
		}

		assertType('mixed~array<mixed, mixed>', $value);

		if (is_iterable($value)) {
			assertType('Traversable<mixed, mixed>', $value);
			return iterator_to_array($value);
		}

		assertType('mixed~array<mixed, mixed>', $value);

		throw new \LogicException();
	}
}
