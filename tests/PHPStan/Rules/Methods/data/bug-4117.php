<?php declare(strict_types = 1);

namespace Bug4117;

use ArrayIterator;
use IteratorAggregate;

/**
 * @refactor Refactor utils into base library
 * @template T of mixed
 * @implements IteratorAggregate<int, T>
 */
class GenericList implements IteratorAggregate
{
    /** @var array<int, T> */
    protected $items = [];

    /**
     * @return ArrayIterator<int, T>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return ?T
     */
	public function broken(int $key)
    {
        $item = $this->items[$key] ?? null;
        if ($item) {
        }

        return $item;
    }

    /**
     * @return ?T
     */
	public function works(int $key)
    {
        $item = $this->items[$key] ?? null;

        return $item;
    }
}
