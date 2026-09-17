<?php declare(strict_types = 1);

namespace ExtDsVersionE2E;

class Bar
{

	/**
	 * @param \Ds\Pair<string, int> $pair
	 * @return \Ds\Pair<string, int>
	 */
	public function doBar(\Ds\Pair $pair): \Ds\Pair
	{
		// Ds\Pair::copy() exists only in ext-ds 1.
		return $pair->copy();
	}

}
