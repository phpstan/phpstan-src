<?php

namespace AddNamesSortedUses;

use AddNamesOther\Alpha;
use AddNamesOther\Gamma;
use function AddNamesOther\someFunction;

/**
 * @property Delta $delta
 */
class Foo
{

	/**
	 * @add-name \AddNamesOther\Beta
	 * @add-name \AddNamesOther\Zeta
	 * @add-name \AddNamesOther\Delta
	 * @add-name \AddNamesOther\someFunction
	 */
	public function doFoo(Alpha $a, Gamma $g): void
	{
		someFunction();
	}

}
