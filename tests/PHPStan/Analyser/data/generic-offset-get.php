<?php

namespace GenericOffsetGet;

use ArrayAccess;
use stdClass;
use function PHPStan\Testing\assertType;

class Foo implements ArrayAccess
{

	#[\ReturnTypeWillChange]
	public function offsetExists($offset)
	{
		return true;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $offset
	 * @return T
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{

	}

	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{

	}

	#[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{

	}

}

function (Foo $foo): void {
	assertType(stdClass::class, $foo->offsetGet(stdClass::class));
	assertType(stdClass::class, $foo[stdClass::class]);
};
