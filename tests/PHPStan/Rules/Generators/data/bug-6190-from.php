<?php

namespace Bug6190From;

class Food
{

	public static function bone(): self
	{
		return new self();
	}

}

/**
 * @param \Generator<int, Food> $good
 * @param \Generator<int, int> $bad
 * @return \Generator<int, Food>|null
 */
function nullableGenerator($good, $bad)
{
	yield from $good;
	yield from $bad;
}
