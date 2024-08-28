<?php

namespace Generics\Bug9630;

/**
 * @template S of A
 */
trait T2
{
	/**
	 * @param S $p
	 * @return S
	 */
	public function getParamFromT2(A $p): ?A
	{
		return $p;
	}
}

