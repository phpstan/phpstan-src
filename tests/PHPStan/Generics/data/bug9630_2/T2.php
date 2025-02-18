<?php

namespace Generics\Bug9630_2;

/**
 * @template T of A
 */
trait T2
{
	/**
	 * @param T $p
	 * @return T
	 */
	public function getParamFromT2(A $p): ?A
	{
		return $p;
	}
}

