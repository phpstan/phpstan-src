<?php

namespace CastObjectToString;

use function method_exists;

/**
 * @param object $object
 * @param object|string $objectOrString
 */
function foo($object, $objectOrString) {
	(string) $object;
	(string) $objectOrString;

	if (method_exists($object, '__toString')) {
		(string) $object;
	}

	if (is_string($objectOrString) || method_exists($objectOrString, '__toString')) {
		(string) $objectOrString;
	}
}
