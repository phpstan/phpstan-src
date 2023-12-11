<?php

namespace Bug5594;

use ArrayIterator;

/**
 * @param numeric-string[] $items
 * @return ArrayIterator<int|string, numeric-string>
 */
function createIterator(array $items): ArrayIterator
{
	return new ArrayIterator($items);
}
