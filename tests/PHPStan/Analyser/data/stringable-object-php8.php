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
