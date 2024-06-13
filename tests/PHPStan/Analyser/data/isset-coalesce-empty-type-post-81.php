<?php // onlyif PHP_VERSION_ID >= 80100

namespace IssetCoalesceEmptyTypePost81;

use function PHPStan\Testing\assertType;

function baz(\ReflectionClass $ref): void {
	assertType('class-string<object>|false', $ref->name ?? false);
}
