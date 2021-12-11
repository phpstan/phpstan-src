<?php

namespace CountableBug;

class Foo implements \Countable
{
	/**
	 * Returns the number of packages in this repository
	 *
	 * @return int Number of packages
	 */
	public function count()
	{
		return 1;
	}
}
