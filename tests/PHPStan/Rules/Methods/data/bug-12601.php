<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug12601;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<non-empty-string, non-empty-string> */
class HelloWorld implements IteratorAggregate
{
	/** @param array<non-empty-string, non-empty-string> $map */
	public function __construct(private array $map) {}

	/** @return Traversable<non-empty-string, non-empty-string> */
	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->map);
	}
}
