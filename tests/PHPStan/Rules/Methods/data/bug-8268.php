<?php declare(strict_types = 1);

namespace Bug8268;

use ArrayIterator;
use Traversable;

class Thing {}

/**
 * @template TKey of array-key
 * @template T of Thing
 * @template-implements ArrayAccess<TKey, T>
 */
class Collection implements ArrayAccess
{
	/** @var array<TKey, T> */
	protected array $elements = [];

	/** @param TKey $offset */
	public function offsetExists(mixed $offset): bool {
		return isset($this->elements[$offset]) || array_key_exists($offset, $this->elements);
	}

	/** @param TKey $offset */
	public function offsetGet(mixed $offset): mixed {
		return $this->elements[$offset] ?? null;
	}

	/**
	 * @param TKey|null $offset
	 * @param T         $value
	 */
	public function offsetSet(mixed $offset, mixed $value): void {
		if ($offset === null) {
			$this->elements[] = $value;
			return;
		}

		//$this->elements[$offset] = $value // No error there...
		$this->set($offset, $value); //Error there...
	}

	/** @param TKey $offset */
	public function offsetUnset(mixed $offset): void {
		unset($this->elements[$offset]);
	}

	/**
	 * @param TKey $key
	 * @param T    $thing
	 */
	final public function set(int|string $key, Thing $thing): void {
		$this->elements[$key] = $thing;
	}
}
