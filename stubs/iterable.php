<?php

/**
 * @template-covariant TKey
 * @template-covariant TValue
 */
interface Traversable
{
}

/**
 * @template-covariant TKey
 * @template-covariant TValue
 *
 * @extends Traversable<TKey, TValue>
 */
interface IteratorAggregate extends Traversable
{

	/**
	 * @return Iterator<TKey, TValue>
	 */
	public function getIterator();

}

/**
 * @template-covariant TKey
 * @template-covariant TValue
 *
 * @extends Traversable<TKey, TValue>
 */
interface Iterator extends Traversable
{

	/**
	 * @return TValue
	 */
	public function current();

	/**
	 * @return TKey
	 */
	public function key();

}

/**
 * @template-covariant TKey
 * @template-covariant TValue
 * @template TSend
 * @template-covariant TReturn
 *
 * @implements Iterator<TKey, TValue>
 */
class Generator implements Iterator
{

	/**
	 * @return TValue
	 */
	public function current() {}

	/**
	 * @return TKey
	 */
	public function key() {}

	/**
	 * @return TReturn
	 */
	public function getReturn() {}

	/**
	 * @param TSend $value
	 * @return TValue
	 */
	public function send($value) {}

}

/**
 * @implements Traversable<mixed, mixed>
 */
class SimpleXMLElement
{

}
