<?php declare(strict_types = 1);

namespace Bug15698;

/**
 * @param 1|2|3 $union
 * @param 8|9|10 $union2
 */
function constIntUnion(int $union, int $union2): void
{
	random_int($union, $union2);
	random_int($union2, $union);
}

/**
 * @param 1|5 $a
 * @param 3|7 $b
 * @param int<1, 3>|int<8, 10> $ranges
 * @param int<20, max>|5 $unbounded
 * @param 1|int $mixedInt
 */
function more(int $a, int $b, int $ranges, int $unbounded, int $mixedInt): void
{
	random_int($a, $b);
	random_int($ranges, 0);
	random_int(11, $ranges);
	random_int($unbounded, 4);
	random_int($unbounded, 6);
	random_int($mixedInt, 0);
}
