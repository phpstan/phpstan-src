<?php

namespace Bug4499;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @param list<int> $things */
	function thing(array $things) : void{
		switch(count($things)){
			case 1:
				assertType('non-empty-array<int<0, max>, int>', $things);
				assertType('int', array_shift($things));
		}
	}

}
