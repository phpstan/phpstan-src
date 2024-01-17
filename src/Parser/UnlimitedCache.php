<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use LogicException;
use function count;
use function sprintf;

/**
 * @template TValue
 * @implements ParserCache<TValue>
 */
class UnlimitedCache implements ParserCache
{

	/** @var array<string, TValue> */
	private array $cache;

	public function __construct()
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

		return $this->cache[$key];
	}

	/**
	 * @param TValue $value
	 */
	public function put(string $key, $value): void
	{
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

}
