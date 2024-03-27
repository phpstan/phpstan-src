<?php

namespace ClosureBindParamClosureThis;

use Closure;

class Foo
{

	/**
	 * @param-closure-this \stdClass $b
	 * @param-closure-this \stdClass $c
	 */
	public function doFoo(\Closure $a, \Closure $b, \Closure $c): void
	{
		Closure::bind($a, new self()); // not checked

		// overwritten
		$b = function (): void {

		};
		Closure::bind($b, new self()); // not checked

		Closure::bind($c, new \stdClass()); // ok
		Closure::bind($c, new self()); // error
	}

}
