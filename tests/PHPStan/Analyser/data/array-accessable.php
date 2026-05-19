<?php

namespace ArrayAccesable;

class Foo implements \ArrayAccess
{

	public function __construct()
	{
		die;
	}

	/**
	 * @return string[]
	 */
	public function returnArrayOfStrings(): array
	{

	}

	/**
	 * @return mixed
	 */
	public function returnMixed()
	{

	}

	/**
	 * @return self|int[]
	 */
	public function returnSelfWithIterableInt(): self
	{

	}

	#[\ReturnTypeWillChange]
	public function offsetExists($offset)
	{

	}

	public function offsetGet($offset): int
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
