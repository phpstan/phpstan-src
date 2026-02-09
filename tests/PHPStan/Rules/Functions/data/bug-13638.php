<?php // lint >= 8.1

namespace Bug13638;

use BackedEnum;

/**
 * @template T of BackedEnum
 * @param ?value-of<T> $a
 * @return ($a is null ? list<?value-of<T>> : list<value-of<T>>)
 */
function test1(int | string | null $a): array
{
	return [$a];
}
