<?php

namespace Bug2580;

use function PHPStan\Testing\assertType;

/**
 * @template T of object
 * @param class-string<T> $typeName
 * @param mixed $value
 */
function cast($value, string $typeName): void {
	if (is_object($value) && get_class($value) === $typeName) {
		assertType('T of object (function Bug2580\cast(), argument)', $value);
	}
}
