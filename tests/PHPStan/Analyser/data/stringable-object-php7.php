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
	assertType('object&hasMethod(__toString)', $object);
}
