<?php // lint >= 8.0

namespace ArrayPointerFunctionOnObject;

use ArrayObject;
use stdClass;

/**
 * @param array<int> $array
 * @param ArrayObject<int, int> $arrayObject
 * @param array<int>|stdClass $arrayOrObject
 * @param iterable<int> $iterable
 */
function doFoo(
	array $array,
	stdClass $object,
	ArrayObject $arrayObject,
	array|stdClass $arrayOrObject,
	iterable $iterable,
	mixed $mixed,
): void
{
	current($array);
	key($array);
	next($array);

	current($object);
	pos($object);
	key($arrayObject);
	next($object);
	prev($object);
	reset($object);
	end($object);
	\current(array: $object);

	current($arrayOrObject);
	current($iterable);
	current($mixed);
}

function intOrObject(int|stdClass $intOrObject): void
{
	current($intOrObject);
}
