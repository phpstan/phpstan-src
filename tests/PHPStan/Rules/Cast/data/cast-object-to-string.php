<?php

namespace CastObjectToString;

/**
 * @param object $object
 * @param stringable-object $stringable
 * @param stringable-object|string $stringableOrString
 */
function foo($object, $stringable, $stringableOrString) {
	(string) $object;
	(string) $stringable;
	(string) $stringableOrString;
}
