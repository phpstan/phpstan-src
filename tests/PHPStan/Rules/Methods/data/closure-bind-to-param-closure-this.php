<?php

namespace ClosureBindToParamClosureThis;

class Foo
{

	/**
	 * @param-closure-this \stdClass $b
	 * @param-closure-this \stdClass $c
	 */
	public function doFoo(\Closure $a, \Closure $b, \Closure $c): void
	{
		$a->bindTo(new self()); // not checked

		// overwritten
		$b = function (): void {

		};
		$b->bindTo(new self()); // not checked

		$c->bindTo(new \stdClass()); // ok
		$c->bindTo(new self()); // error
	}

}
