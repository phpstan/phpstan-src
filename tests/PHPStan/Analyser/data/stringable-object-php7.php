<?php

namespace StringableObjectPhp7;

use function PHPStan\Testing\assertType;

interface Stringable
{
	public function __toString();
}

/**
 * @param stringable-object $object
 */
function foo($object)
{
	assertType('stringable-object', $object);
}

/**
 * @param object $object
 */
function bar($object)
{
	assertType('object', $object);
	if (method_exists($object, '__toString')) {
		assertType('stringable-object', $object);
	}
}
