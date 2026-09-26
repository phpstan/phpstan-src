<?php // lint >= 8.0

namespace ClosureSignatureFromUsagesGenericsRule;

/** @template T */
class Collection
{

	/** @param array<T> $items */
	public function __construct(public array $items = [])
	{
	}

}

/** @param Collection<int> $c */
function takesInts(Collection $c): void
{
}

function (): void {
	$c = function ($x) {
		return $x;
	};
	$col = new Collection([1, 2]);
	takesInts($col);
	array_map($c, $col->items);
};
