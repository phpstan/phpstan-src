<?php declare(strict_types = 1);

namespace Bug7049;

class Collection
{
	/**
	 * @template TGroupKey as array-key
	 * @param TGroupKey|Closure(mixed): TGroupKey $key
	 * @return array<TGroupKey, static>
	 */
	public function groupBy(int|string|Closure $key): array
	{
		return [];
	}
}

(function () {
	$collection = new Collection();
	$collection->groupBy('id');
})();
