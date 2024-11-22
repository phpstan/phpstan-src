<?php declare(strict_types = 1);

namespace Bug12102;

use Iterator;
use IteratorAggregate;
use Traversable;

class HelloWorld
{
	/** @param Traversable<mixed, mixed> $traversable */
	public function sayHello(Traversable $traversable): ?Iterator
	{
		if (!$traversable instanceof IteratorAggregate) {
			return $traversable;
		}

		return null;
	}

	/** @param iterable<mixed, mixed> $iterable */
	public function sayHello2(iterable $iterable): ?Iterator
	{
		if (\is_array($iterable)) {
			return null;
		}

		if (!$iterable instanceof IteratorAggregate) {
			return $iterable;
		}

		return null;
	}
}
