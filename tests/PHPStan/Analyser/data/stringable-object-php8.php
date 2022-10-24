<?php

namespace StringableObjectPhp8;

use function PHPStan\Testing\assertType;

/**
 * @param stringable-object $object
 */
function foo($object)
{
	assertType('Stringable', $object);
}

/**
 * @param object $object
 */
function bar($object)
{
	assertType('object', $object);
	if (method_exists($object, '__toString')) {
		assertType('Stringable', $object);
	}
}
