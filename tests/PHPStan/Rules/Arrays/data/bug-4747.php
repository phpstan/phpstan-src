<?php declare(strict_types = 1);

namespace Bug4747;

/**
 * @param array{a_1: array{int, string}, a_2: array{int, string}, hi: int} $r
 * @return string
 */
function x(array $r, string $d) : string
{
	assert($d === '1' || $d === '2');

	return $r['a_' . $d][1];
}
