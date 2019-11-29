<?php

/**
 * @template TKey
 * @template TValue
 */
interface ArrayAccess
{

	/**
	 * @param TKey $offset
	 * @return bool
	 */
	public function offsetExists($offset);

	/**
	 * @param TKey $offset
	 * @return TValue|null
	 */
	public function offsetGet($offset);

	/**
	 * @param TKey $offset
	 * @param TValue $value
	 * @return void
	 */
	public function offsetSet($offset, $value);

	/**
	 * @param TKey $offset
	 * @return void
	 */
	public function offsetUnset($offset);

}

/**
 * @template TKey
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 * @implements ArrayAccess<TKey, TValue>
 */
class ArrayObject implements IteratorAggregate, ArrayAccess
{

	/**
	 * @param array<TKey, TValue>|object $input
	 * @param int $flags
	 * @param class-string $iterator_class
	 */
	public function __construct($input = null, $flags = 0, $iterator_class = "ArrayIterator") { }

	/**
	 * @param TKey $index
	 * @return bool
	 */
	public function offsetExists($index) { }

	/**
	 * @param TKey $index
	 * @return TValue
	 */
	public function offsetGet($index) { }

	/**
	 * @param TKey $index
	 * @param TValue $newval
	 * @return void
	 */
	public function offsetSet($index, $newval) { }

	/**
	 * @param TKey $index
	 * @return void
	 */
	public function offsetUnset($index) { }

	/**
	 * @param TValue $value
	 * @return void
	 */
	public function append($value) { }

	/**
	 * @return array<TKey, TValue>
	 */
	public function getArrayCopy() { }

	/**
	 * @param callable(TValue, TValue): int $cmp_function
	 * @return void
	 */
	public function uasort($cmp_function) { }

	/**
	 * @param callable(TKey, TKey): int $cmp_function
	 * @return void
	 */
	public function uksort($cmp_function) { }

	/**
	 * @return ArrayIterator<TKey, TValue>
	 */
	public function getIterator() { }

	/**
	 * @param class-string $iterator_class
	 * @return void
	 */
	public function setIteratorClass($iterator_class) { }

}
