<?php // lint >= 8.4

declare(strict_types = 1);

namespace WeakPurityObjects;

use ReflectionProperty;
use stdClass;

/**
 * @phpstan-pure
 */
function writesObjectParameter(ReflectionProperty $property, stdClass $object): int
{
	$property->setRawValue($object, 1);

	return 1;
}

/**
 * @phpstan-pure
 */
function writesFreshObject(ReflectionProperty $property): int
{
	$property->setRawValue(new stdClass(), 1);

	return 1;
}
