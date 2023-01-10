<?php

namespace AlwaysTrueElseif;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	/**
	 * @param 'a'|'b'|'c' $s
	 * @return void
	 */
	public function doFoo(string $s): void
	{
		if ($s === 'a') {
			$a = true;
		} elseif ($s === 'b') {
			$a = false;
		} elseif ($s === 'c') {
			$a = true;
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	}

}
