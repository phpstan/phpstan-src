<?php declare(strict_types=1);

namespace Bug8881;

/**
 * @param int[] $a
 * @return int
 */
function pop($a)
{
	assert((bool)$a);
	return array_pop($a);
}
