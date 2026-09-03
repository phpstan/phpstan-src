<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug8031;

/**
 * @template TKey of array-key
 * @template TValue of mixed
 */
class Collection
{
	/**
	 * @param array<TKey, TValue> $val
	 */
	public function __construct(protected array $val) {}
}

/**
 * @return Collection<'one'|'two', int>
 */
function test(): Collection
{
	return new Collection([
    	'one' => 1,
    	'two'=> 2
	]);
}
