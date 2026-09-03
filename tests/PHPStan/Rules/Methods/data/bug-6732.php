<?php

namespace Bug6732Methods;

/** @template T */
class Collection
{

	/** @param array<T> $items */
	public function __construct(array $items = [])
	{
	}

	/** @param T $item */
	public function add($item): void
	{
	}

}

class Foo
{

	/** @var Collection<int> */
	private Collection $ints;

	public function lowerBoundsOnly(): void
	{
		$ints = new Collection();
		$ints->add(1);
		$ints->add("a");
	}

	public function sendWinsOverLowerBound(): void
	{
		$c = new Collection();
		$this->ints = $c;
		$c->add('a');
	}

}
