<?php

namespace ResultCacheE2EConstants;

class ClassUsingConstant
{

	/**
	 * @return int<1, 3>
	 */
	public function getMode(): int
	{
		return SOME_MODE;
	}

}
