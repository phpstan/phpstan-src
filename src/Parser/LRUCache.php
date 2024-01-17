<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use LogicException;
use function array_key_first;
use function array_keys;
use function count;
use function sprintf;

/**
 * @template TValue
 * @implements ParserCache<TValue>
 */
class LRUCache implements ParserCache
{

	/** @var array<string, TValue> */
	private array $cache;

	public function __construct(private int $capacity)
	{
		$this->cache = [];
	}

	/**
	 * @return TValue
	 */
	public function get(string $key)
	{
		if (!isset($this->cache[$key])) {
			throw new LogicException(sprintf('Key %s was not found in the cache, use ->has() first', $key));
		}

		$value = $this->cache[$key];
		unset($this->cache[$key]);
		$this->cache[$key] = $value;

		return $this->cache[$key];
	}

	/**
	 * @param TValue $value
	 */
	public function put(string $key, $value): void
	{
		if (count($this->cache) >= $this->capacity) {
			unset($this->cache[array_key_first($this->cache)]);
		}

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

	/**
	 * @return list<array-key>
	 */
	public function getKeys(): array
	{
		return array_keys($this->cache);
	}

}
