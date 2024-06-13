<?php // onlyif PHP_VERSION_ID < 80100

namespace IssetCoalesceEmptyTypePre81;

use function PHPStan\Testing\assertType;

function baz(\ReflectionClass $ref): void {
	assertType('class-string<object>', $ref->name ?? false);
}
