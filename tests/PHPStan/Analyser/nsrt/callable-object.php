<?php

namespace CallableObject;

use Iterator;
use function PHPStan\Testing\assertType;

/**
 * @param object $object
 * @param callable-object $callableObject
 */
function foo(callable $callable, $object, $callableObject)
{
	assertType('callable(): mixed', $callable);
	assertType('object', $object);
	assertType('callable-object', $callableObject);

	if (is_object($callable)) {
		assertType('callable-object', $callable);
	}

	if (is_callable($object)) {
		assertType('callable-object', $object);
	}

	if ($callable === $object) {
		assertType('callable-object', $callable);
		assertType('callable-object', $object);
	}
}

/**
 * @param Iterator&callable $object
 */
function bar($object)
{
	assertType('callable(): mixed&Iterator', $object);
}
