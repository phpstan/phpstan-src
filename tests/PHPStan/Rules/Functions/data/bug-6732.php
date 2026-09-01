<?php

namespace Bug6732Functions;

/** @template T */
class Collection
{

	/** @param array<T> $items */
	public function __construct(array $items = [])
	{
	}

}

/** @param Collection<int> $ints */
function takeInts(Collection $ints): void
{
}

/** @param Collection<string> $strings */
function takeStrings(Collection $strings): void
{
}

function (): void {
	$ints = new Collection();
	takeInts($ints);
	takeStrings($ints);
};
