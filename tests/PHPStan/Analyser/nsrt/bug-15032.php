<?php declare(strict_types = 1);

namespace Bug15032;

use ReflectionObject;
use function PHPStan\Testing\assertType;

/**
 * @template T of object
 * @param T $object
 * @return T
 */
function createInstance(object $object): object
{
	$ref = new ReflectionObject($object);
	assertType('ReflectionObject<T of object (function Bug15032\createInstance(), argument)>', $ref);

	$ret = $ref->newInstance();
	assertType('T of object (function Bug15032\createInstance(), argument)', $ret);

	return $ret;
}

function concreteObject(\Exception $e): void
{
	$ref = new ReflectionObject($e);
	assertType('ReflectionObject<Exception>', $ref);
	assertType('class-string<Exception>', $ref->getName());
	assertType('class-string<Exception>', $ref->name);
	assertType('Exception', $ref->newInstance());
	assertType('Exception', $ref->newInstanceArgs([]));
	assertType('Exception', $ref->newInstanceWithoutConstructor());
}
