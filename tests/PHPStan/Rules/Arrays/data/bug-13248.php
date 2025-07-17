<?php

namespace Bug13248;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

class X
{
}

/**
 * @implements IteratorAggregate<int, string>
 */
class Y extends X implements IteratorAggregate
{
	/**
	 * @return ArrayIterator<int<0, 2>, 'a'|'b'|'c'>
	 */
	public function getIterator(): Traversable
	{
		return new ArrayIterator(['a', 'b', 'c']);
	}
}

/**
 * @return X&Traversable<int, string>
 */
function y(): X
{
	return new Y();
}

foreach (y() as $item) { // hm?
	echo $item . PHP_EOL;
}
