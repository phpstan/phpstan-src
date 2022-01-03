<?php

namespace Bug6158;

/**
 * @template TKey of array-key
 * @template T
 * @implements ArrayAccess<TKey|null, T>
 */
class Collection implements \ArrayAccess {

	/** @var array<TKey|null, T> */
	private array $values;

	/**
	 * @param TKey $offset
	 */
	final public function offsetExists(mixed $offset): bool
	{
		return array_key_exists($offset, $this->values);
	}

	/**
	 * @param TKey $offset
	 *
	 * @return T
	 */
	final public function offsetGet(mixed $offset): mixed
	{
		return $this->values[$offset];
	}

	/**
	 * @param TKey|null $offset
	 * @param T    $value
	 */
	final public function offsetSet($offset, $value): void
	{
		$this->values[$offset] = $value;
	}

	/**
	 * @param TKey $offset
	 */
	final public function offsetUnset($offset): void
	{
		unset($this->values[$offset]);
	}

	/** @return T|null */
	final public function randValue(): mixed
	{
		if ($this->values === []) {
			return null;
		}

		return $this[array_rand($this->values)];
	}
}

final class User {

	public UserCollection $users;

	public function __construct()
	{
		$this->users = new UserCollection();
	}

	public function randValue(): ?User
	{
		return $this->rand();
	}

	private function rand(): ?User
	{
		return $this->users->randValue();
	}

}

/**
 * @extends Collection<array-key, User>
 */
class UserCollection extends Collection
{
}
