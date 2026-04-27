<?php declare(strict_types = 1);

namespace Bug10749;

use ArrayAccess;

/**
 * @template T of array<string, mixed>
 * @implements ArrayAccess<key-of<T>, value-of<T>>
 */
abstract class Base implements ArrayAccess
{

	/** @var T */
	protected array $data = [];

	/**
	 * @template K of key-of<T>
	 * @param K|null $offset
	 * @param T[K] $value
	 */
	public function offsetSet($offset, $value): void
	{
		$this->data[$offset] = $value;
	}

}
