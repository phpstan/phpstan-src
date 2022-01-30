<?php

use function PHPStan\Testing\assertType;

function baz(\ReflectionClass $ref): void {
	assertType('class-string<object>|false', $ref->name ?? false);
}
