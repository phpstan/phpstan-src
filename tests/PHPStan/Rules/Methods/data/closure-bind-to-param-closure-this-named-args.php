<?php // lint >= 8.0

namespace ClosureBindToParamClosureThisNamedArgs;

class Foo
{

	/**
	 * @param-closure-this \stdClass $c
	 */
	public function doFoo(\Closure $c): void
	{
		$c->bindTo(newThis: new \stdClass()); // ok
		$c->bindTo(newThis: new self()); // error
		$c->bindTo(newScope: null, newThis: new \stdClass()); // ok
		$c->bindTo(newScope: null, newThis: new self()); // error
	}

}
