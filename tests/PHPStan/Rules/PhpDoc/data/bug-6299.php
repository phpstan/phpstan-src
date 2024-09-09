<?php

namespace Bug6299;

use stdClass;

class HelloWorld
{
	/**
	 * @phpstan-return array{'numeric': stdClass[], 'branches': array{'names': string[], 'exclude': bool}}}|int
	 */
	public function sayHello(): array
	{
		if(rand(0,1)){
			return ['numeric' => [], 'branches' => ['names' => [], 'exclude' => false]];
		}
		else {
			return 0;
		}
	}
}
