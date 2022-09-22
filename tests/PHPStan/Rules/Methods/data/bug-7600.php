<?php

namespace Bug7600;

/** @template T */
class Collection
{
	/** @param non-empty-list<T> $array */
	public function __construct(public array $array) {}

	/** @return T */
	public function getFirst(): mixed
	{
		return $this->array[0];
	}

	/** @param T $item */
	public function remove(mixed $item): void {}
}

function (): void {
	$ints = new Collection([1, 2]);
	$strings = new Collection(['foo', 'bar']);

	$collection = rand(0, 1) === 0 ? $ints : $strings;

	$collection->remove($collection->getFirst());
};
