<?php declare(strict_types = 1);

namespace Bug15128Methods;

/**
 * @template TKey of int|string
 * @template TValue of int|string
 */
interface Store
{

	/**
	 * @return (TKey is int ? list<TValue> : (TValue is int ? int : string))
	 */
	public function get();

	/**
	 * @param int|string $key
	 * @return (TKey is int ? list<TValue> : ($key is int ? int : string))
	 */
	public function find($key);

}

/**
 * @template TValue of int|string
 * @implements Store<string, TValue>
 */
class StringStore implements Store
{

	public function get()
	{
		return 1;
	}

	public function find($key)
	{
		return 1;
	}

}
