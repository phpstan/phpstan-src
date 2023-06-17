<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug9161;

/**
 * @template-covariant TKey of int|string
 * @template-covariant TValue
 */
final class Map
{
	/**
	 * @param array<TKey, TValue> $items
	 */
	public function __construct(
		private array $items = [],
	) {
	}

	/**
	 * @return array<TKey, TValue>
	 */
	public function toArray(): array
	{
		return $this->items;
	}

	/**
	 * @return list<array{0: TKey, 1: TValue}>
	 */
	public function toPairs(): array
	{
		$pairs = [];
		foreach ($this->items as $key => $value) {
			$pairs[] = [$key, $value];
		}

		return $pairs;
	}
}
