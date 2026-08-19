<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function array_key_first;
use function count;
use function sprintf;

/**
 * A least-recently-used cache backed by an array, bounded by entry count, by the total
 * weight of its entries, or by both.
 *
 * PHP arrays keep insertion order, so the least recently used entry is the first one and
 * touching an entry means re-inserting it at the end. Callers decide what an entry weighs:
 * for a cache of file contents that is the length of the contents, for one keyed by source
 * code it is the length of the key.
 *
 * @template TValue
 */
final class LruCache
{

	/** @var array<string, TValue> insertion order is the LRU order, oldest first */
	private array $values = [];

	/** @var array<string, int> */
	private array $weights = [];

	private int $weight = 0;

	/**
	 * @param int $maxCount maximum number of entries, 0 for no limit
	 * @param int $maxWeight maximum total weight, 0 for no limit
	 * @param int $weightEvictionFloorCount weight eviction stops at this many entries, so a
	 * single entry heavier than $maxWeight cannot flush the whole cache on every insertion
	 */
	public function __construct(
		private int $maxCount = 0,
		private int $maxWeight = 0,
		private int $weightEvictionFloorCount = 0,
	)
	{
	}

	/**
	 * The entry, touched so it becomes the most recently used one, or null when there is none.
	 *
	 * @return TValue|null
	 */
	public function get(string $key): mixed
	{
		if (!array_key_exists($key, $this->values)) {
			return null;
		}

		$value = $this->values[$key];
		unset($this->values[$key]);
		$this->values[$key] = $value;

		return $value;
	}

	/**
	 * Stores an entry, evicting least recently used ones until it fits.
	 *
	 * An entry already stored under this key is replaced, its weight accounted for again, and
	 * the entry becomes the most recently used one.
	 *
	 * @param TValue $value
	 * @return list<string> the keys evicted to make room, so a caller keeping data alongside
	 * this cache can drop the same entries
	 */
	public function set(string $key, mixed $value, int $weight): array
	{
		if (array_key_exists($key, $this->values)) {
			$this->weight -= $this->weights[$key];
			unset($this->values[$key], $this->weights[$key]);
		}

		$evicted = $this->evict($weight);

		$this->values[$key] = $value;
		$this->weights[$key] = $weight;
		$this->weight += $weight;

		return $evicted;
	}

	/**
	 * Replaces the value of an existing entry and touches it, leaving its weight as it was.
	 *
	 * This is for a value that grew a better representation rather than different contents -
	 * nothing is evicted, because nothing new is taking up room.
	 *
	 * @param TValue $value
	 */
	public function replace(string $key, mixed $value): void
	{
		if (!array_key_exists($key, $this->values)) {
			throw new ShouldNotHappenException(sprintf('Cannot replace %s, it is not in the cache.', $key));
		}

		unset($this->values[$key]);
		$this->values[$key] = $value;
	}

	public function count(): int
	{
		return count($this->values);
	}

	/**
	 * @return array<string, TValue> in LRU order, oldest first
	 */
	public function all(): array
	{
		return $this->values;
	}

	/**
	 * @return list<string>
	 */
	private function evict(int $incomingWeight): array
	{
		$evicted = [];
		while (
			($this->maxCount > 0 && count($this->values) >= $this->maxCount)
			|| (
				$this->maxWeight > 0
				&& $this->weight + $incomingWeight > $this->maxWeight
				&& count($this->values) > $this->weightEvictionFloorCount
			)
		) {
			$oldestKey = array_key_first($this->values);
			if ($oldestKey === null) {
				break;
			}

			$this->weight -= $this->weights[$oldestKey];
			unset($this->values[$oldestKey], $this->weights[$oldestKey]);
			$evicted[] = $oldestKey;
		}

		return $evicted;
	}

}
