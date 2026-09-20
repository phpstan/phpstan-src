<?php // lint >= 8.0

namespace ClosureBindParamClosureThisNamedArgs;

use Closure;

class Foo
{

	/**
	 * @param-closure-this \stdClass $c
	 */
	public function doFoo(\Closure $c): void
	{
		Closure::bind(closure: $c, newThis: new \stdClass()); // ok
		Closure::bind(closure: $c, newThis: new self()); // error
		Closure::bind(newThis: new \stdClass(), closure: $c); // ok
		Closure::bind(newThis: new self(), closure: $c); // error
	}

}
