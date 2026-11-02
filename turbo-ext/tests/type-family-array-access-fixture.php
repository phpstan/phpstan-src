<?php declare(strict_types = 1);

namespace PHPStanTurboTests;

/**
 * offsetGet() and offsetSet() with method-level template types: the
 * ObjectType offset operations resolve them against the offset (and the
 * written value), as for an explicit offsetGet()/offsetSet() call.
 *
 * @template TArray of array
 * @implements \ArrayAccess<key-of<TArray>, value-of<TArray>>
 */
final class TemplatedArrayAccessFixture implements \ArrayAccess
{

	/**
	 * @param key-of<TArray> $offset
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists($offset)
	{
		return true;
	}

	/**
	 * @template TOffset of key-of<TArray>
	 * @param TOffset $offset
	 * @return TArray[TOffset]
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		throw new \LogicException();
	}

	/**
	 * @template TOffset of key-of<TArray>
	 * @param TOffset $offset
	 * @param TArray[TOffset] $value
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{
	}

	/**
	 * @template TOffset of key-of<TArray>
	 * @param TOffset $offset
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{
	}

}

/**
 * offsetSet() with a value type conditional on the offset: an append
 * resolves it against a null offset.
 *
 * @implements \ArrayAccess<int|null, int|string>
 */
final class ConditionalArrayAccessFixture implements \ArrayAccess
{

	#[\ReturnTypeWillChange]
	public function offsetExists($offset)
	{
		return true;
	}

	/**
	 * @return ($offset is null ? int : string)
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		throw new \LogicException();
	}

	/**
	 * @param int|null $offset
	 * @param ($offset is null ? int : string) $value
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{
	}

	#[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{
	}

}
