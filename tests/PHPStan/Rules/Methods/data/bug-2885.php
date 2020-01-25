<?php

namespace Bug2885;

class Test
{
	/**
	 * @return static
	 */
	function do()
	{
		return $this->do()->do();
	}
}
