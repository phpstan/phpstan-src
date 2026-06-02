<?php

namespace Bug14718;

trait FooTrait
{

	public function doFoo(int $i): void
	{
		if (abs($i) === false) { // @phpstan-ignore identical.alwaysFalse

		}
	}

}
