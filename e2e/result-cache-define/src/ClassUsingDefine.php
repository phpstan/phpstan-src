<?php

namespace ResultCacheE2EDefine;

class ClassUsingDefine
{

	/**
	 * @return int<1, 3>
	 */
	public function getMode(): int
	{
		return SOME_DEFINED_MODE;
	}

}
