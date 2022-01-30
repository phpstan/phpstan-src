<?php

use function PHPStan\Testing\assertType;

function baz(\ReflectionClass $ref): void {
	assertType('string', $ref->name ?? false);
}
