<?php

namespace CountableBug;

class Foo implements \Countable
{
	/**
	 * Returns the number of packages in this repository
	 *
	 * @return int Number of packages
	 */
	#[\ReturnTypeWillChange]
	public function count()
	{
		return 1;
	}
}
