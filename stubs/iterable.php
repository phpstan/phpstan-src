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
	 * @return Traversable<TKey, TValue>
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
class SimpleXMLElement implements Traversable
{

}

/**
 * @template-covariant TKey
 * @template-covariant TValue
 * @extends Iterator<TKey, TValue>
 */
interface SeekableIterator extends Iterator
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
 * @template TKey
 * @template TValue
 * @implements SeekableIterator<TKey, TValue>
 * @implements ArrayAccess<TKey, TValue>
 */
class ArrayIterator implements SeekableIterator, ArrayAccess, Countable, Serializable
{

	/**
	 * @param array<TKey, TValue> $array
	 * @param int $flags
	 */
	public function __construct($array = array(), $flags = 0) { }

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
	 * @return TValue
	 */
	public function current();

	/**
	 * @return TKey
	 */
	public function key();

}

class DOMDocument
{

	/**
	 * @return DOMNodeList<DOMElement>
	 */
	public function getElementsByTagName ($name) {}

	/**
	 * @return DOMNodeList<DOMElement>
	 */
	public function getElementsByTagNameNS ($namespaceURI, $localName) {}

}

class DOMNode
{

}

class DOMElement extends DOMNode
{

	/**
	 * @return DOMNodeList<DOMElement>
	 */
	public function getElementsByTagName ($name) {}

	/**
	 * @return DOMNodeList<DOMElement>
	 */
	public function getElementsByTagNameNS ($namespaceURI, $localName) {}

}

/**
 * @template-covariant TNode as DOMNode
 * @implements Traversable<int, TNode>
 */
class DOMNodeList implements Traversable
{

	/**
	 * @return TNode|null
	 */
	public function item ($index) {}

}
