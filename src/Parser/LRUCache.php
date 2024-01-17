<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use LogicException;
use SplDoublyLinkedList;
use function count;
use function sprintf;

/**
 * @template TValue
 */
class LRUCache
{

	/** @var array<string, TValue> */
	private array $cache;

	/** @var SplDoublyLinkedList<string> */
	private SplDoublyLinkedList $list;

	public function __construct(private int $capacity)
	{
		$this->cache = [];
		$this->list = new SplDoublyLinkedList();
	}

	/**
	 * @return TValue
	 */
	public function get(string $key)
	{
		if (!isset($this->cache[$key])) {
			throw new LogicException(sprintf('Key %s was not found in the cache, use ->has() first', $key));
		}

		$this->list->rewind();
		while ($this->list->current() !== $key) {
			$this->list->next();
		}
		$this->list->rewind();
		$this->list->unshift($this->list->pop());

		return $this->cache[$key];
	}

	/**
	 * @param TValue $value
	 */
	public function put(string $key, $value): void
	{
		if (count($this->cache) >= $this->capacity) {
			$removedKey = $this->list->pop();
			unset($this->cache[$removedKey]);
		}

		$this->list->unshift($key);
		$this->cache[$key] = $value;
	}

	public function has(string $key): bool
	{
		return isset($this->cache[$key]);
	}

	public function getCount(): int
	{
		return count($this->cache);
	}

	public function getCapacity(): int
	{
		return $this->capacity;
	}

}
