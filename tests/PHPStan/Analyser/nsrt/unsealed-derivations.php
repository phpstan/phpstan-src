<?php

namespace UnsealedDerivations;

use function PHPStan\Testing\assertType;

class FilterFalsey
{

	/**
	 * @param array{a: int, ...<string, int|null>} $arr
	 */
	public function filterUnsealed(array $arr): void
	{
		// `array_filter` drops falsey entries from both the explicit slot
		// and the unsealed extras. The unsealed value type must have the
		// falsey union (`null|false|0|0.0|''|'0'|[]`) subtracted too —
		// here `int|null` collapses to non-zero `int`.
		assertType(
			'array{a?: int<min, -1>|int<1, max>, ...<string, int<min, -1>|int<1, max>>}',
			array_filter($arr),
		);
	}

}
