<?php declare(strict_types = 1);

namespace FunctionCallSeededRandom;

/**
 * @param list<int> $a
 * @param non-empty-list<string> $list
 */
function doFoo(array $a, array $list): void
{
	// they seed or advance the seeded random number generator
	mt_rand();
	rand();
	mt_srand(1);
	srand(1);
	shuffle($a);
	str_shuffle('ab');
	array_rand($list);

	// it has a generator of its own that mt_srand() and srand() do not seed
	lcg_value();
}
