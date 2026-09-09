<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug7049;

use Closure;
use function PHPStan\Testing\assertType;

class Collection
{

	/**
	 * @template TGroupKey of array-key
	 * @param TGroupKey|Closure(mixed): TGroupKey $key
	 * @return array<TGroupKey, static>
	 */
	public function groupBy(int|string|Closure $key): array
	{
		return [];
	}

}

$collection = new Collection();
assertType("array<'id', Bug7049\Collection>", $collection->groupBy('id'));
